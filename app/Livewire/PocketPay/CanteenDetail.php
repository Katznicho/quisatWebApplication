<?php

namespace App\Livewire\PocketPay;

use App\Exceptions\PocketPayException;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Models\SmsMessage;
use App\Services\CanteenAttendantAuthService;
use App\Services\CanteenReportService;
use App\Services\MarzSmsService;
use App\Services\PocketPayService;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full canteen dashboard: summary, sales, float statement, attendants, cardholders,
 * reversals and PDF reports for a chosen period.
 */
class CanteenDetail extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    public const TABS = ['overview', 'sales', 'statement', 'attendants', 'students', 'reversals'];

    public Canteen $canteen;

    #[Url]
    public string $tab = 'overview';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function mount(Canteen $canteen): void
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($canteen);

        $this->canteen = $canteen->load('business');

        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'overview';
        }

        $this->syncPeriod();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    public function preset(string $preset): void
    {
        $today = CarbonImmutable::now($this->reports()->timezone($this->canteen))->startOfDay();

        [$from, $to] = match ($preset) {
            'today' => [$today, $today],
            '7d' => [$today->subDays(6), $today],
            '30d' => [$today->subDays(29), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'year' => [$today->startOfYear(), $today],
            default => [$today->startOfMonth(), $today],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to'], true)) {
            $this->syncPeriod();
        }

        if (in_array($property, ['from', 'to', 'search', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function period(): array
    {
        return $this->reports()->period($this->canteen, $this->from, $this->to);
    }

    #[Computed]
    public function report(): array
    {
        [$from, $to] = $this->period;

        return $this->reports()->build($this->canteen, $from, $to);
    }

    public function render(): View
    {
        [$from, $to] = $this->period;
        [$start, $end] = $this->reports()->bounds($from, $to);
        $search = trim($this->search);

        return view('livewire.pocket-pay.canteen-detail', [
            'report' => $this->report,
            'sales' => $this->tab === 'sales'
                ? $this->reports()->salesQuery($this->canteen, $start, $end)
                    ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                    ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                        ->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                        ->orWhereHas('card', fn ($q) => $q->where('card_number', 'like', '%'.preg_replace('/\s+/', '', $search).'%'))))
                    ->latest('id')
                    ->paginate(25)
                : null,
            'ledger' => $this->tab === 'statement'
                ? $this->reports()->ledgerQuery($this->canteen, $start, $end)->paginate(50)
                : null,
            'cardholders' => $this->tab === 'students'
                ? $this->reports()->cardholdersQuery($this->canteen, $start, $end)
                    ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                        ->where('card_number', 'like', '%'.preg_replace('/\s+/', '', $search).'%')
                        ->orWhereHas('student', fn ($q) => $q
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%"))))
                    ->orderByDesc('spent_here')
                    ->orderBy('id')
                    ->paginate(25)
                : null,
            'reversals' => $this->tab === 'reversals'
                ? CanteenSale::query()
                    ->where('canteen_id', $this->canteen->id)
                    ->whereNotNull('reversal_status')
                    ->with(['student:id,first_name,last_name', 'attendant:id,name', 'reviewer:id,name', 'card:id,card_number'])
                    ->orderByRaw('CASE WHEN reversal_status = ? THEN 0 ELSE 1 END', [CanteenSale::REVERSAL_PENDING])
                    ->latest('reversal_requested_at')
                    ->paginate(25)
                : null,
        ]);
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    public function topupAction(): Action
    {
        return Action::make('topup')
            ->label('Top up float')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (): bool => $this->canteen->isActive() && PocketPayAccess::canManageFloat())
            ->modalHeading('Top up '.$this->canteen->name)
            ->modalDescription('Record cash paid at the canteen. The float is credited immediately and the proof is kept with the statement.')
            ->form(ListCanteens::floatTopupFields())
            ->action(fn (array $data) => ListCanteens::recordFloatTopup($this->canteen, $data));
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->fillForm(fn (): array => $this->canteen->only(['name', 'location', 'low_float_threshold', 'status']))
            ->form([
                ...ListCanteens::canteenFields(),
                Forms\Components\Select::make('status')
                    ->placeholder('Choose a status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->required()
                    ->helperText('Attendants of an inactive canteen cannot sell.'),
            ])
            ->action(function (array $data): void {
                $this->canteen->update([
                    'name' => $data['name'],
                    'location' => $data['location'] ?? null,
                    'low_float_threshold' => (int) ($data['low_float_threshold'] ?? 0),
                    'status' => $data['status'],
                ]);

                Notification::make()->title('Canteen updated')->success()->send();
            });
    }

    public function addAttendantAction(): Action
    {
        return Action::make('addAttendant')
            ->label('Add attendant')
            ->icon('heroicon-o-user-plus')
            ->modalDescription('The attendant receives an SMS with a one-time PIN to set up their own 4-digit PIN in the Quisat Attendant app.')
            ->form([
                Forms\Components\TextInput::make('name')->required()->maxLength(120)->placeholder('e.g. Grace Nakato'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->placeholder('e.g. 0772 123 456')
                    ->required()
                    ->helperText('The attendant logs in with this number.'),
            ])
            ->action(function (array $data, Action $action): void {
                $phone = app(MarzSmsService::class)->normalizePhone($data['phone']);

                if (! $phone) {
                    Notification::make()->title('Enter a valid phone number.')->danger()->send();
                    $action->halt();
                }

                if (CanteenAttendant::withTrashed()->where('phone', $phone)->exists()) {
                    Notification::make()->title('This phone number is already used by an attendant.')->danger()->send();
                    $action->halt();
                }

                $attendant = CanteenAttendant::create([
                    'business_id' => $this->canteen->business_id,
                    'canteen_id' => $this->canteen->id,
                    'name' => $data['name'],
                    'phone' => $phone,
                    'status' => CanteenAttendant::STATUS_PENDING_SETUP,
                    'created_by' => Auth::id(),
                ]);

                $this->sendOtp($attendant, 'Attendant added');
            });
    }

    public function sendOtpAction(): Action
    {
        return Action::make('sendOtp')
            ->label('Send PIN')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('info')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Send a one-time PIN')
            ->modalDescription('The attendant uses it in the app to set a new 4-digit PIN.')
            ->action(function (array $arguments): void {
                $attendant = $this->attendant($arguments);

                if ($attendant->status !== CanteenAttendant::STATUS_DISABLED) {
                    $this->sendOtp($attendant, 'One-time PIN');
                }
            });
    }

    public function disableAttendantAction(): Action
    {
        return Action::make('disableAttendant')
            ->label('Deactivate')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Deactivate attendant')
            ->modalDescription('The attendant is signed out of the app and cannot log in until activated again.')
            ->action(function (array $arguments): void {
                $attendant = $this->attendant($arguments);
                $attendant->update(['status' => CanteenAttendant::STATUS_DISABLED]);
                $attendant->tokens()->delete();
                Notification::make()->title('Attendant deactivated')->success()->send();
            });
    }

    public function enableAttendantAction(): Action
    {
        return Action::make('enableAttendant')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Activate attendant')
            ->action(function (array $arguments): void {
                $attendant = $this->attendant($arguments);
                $attendant->update([
                    'status' => $attendant->pin ? CanteenAttendant::STATUS_ACTIVE : CanteenAttendant::STATUS_PENDING_SETUP,
                ]);
                Notification::make()->title('Attendant activated')->success()->send();
            });
    }

    public function deactivateCardAction(): Action
    {
        return Action::make('deactivateCard')
            ->label('Deactivate')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Deactivate card')
            ->modalDescription('A deactivated card cannot buy or be topped up until it is activated again. The balance is kept.')
            ->action(function (array $arguments): void {
                app(PocketPayService::class)->setBlocked($this->card($arguments), true, Auth::user());
                Notification::make()->title('Card deactivated')->success()->send();
            });
    }

    public function activateCardAction(): Action
    {
        return Action::make('activateCard')
            ->label('Activate')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Activate card')
            ->modalDescription('The card can buy again. A card that never had a PIN set still needs one from the attendant or parent app.')
            ->action(function (array $arguments): void {
                app(PocketPayService::class)->setBlocked($this->card($arguments), false, Auth::user());
                Notification::make()->title('Card activated')->success()->send();
            });
    }

    public function approveReversalAction(): Action
    {
        return Action::make('approveReversal')
            ->label('Approve')
            ->visible(fn (): bool => PocketPayAccess::canReviewReversals())
            ->icon('heroicon-o-check')
            ->color('success')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription('The card is refunded and the amount returns to the canteen float.')
            ->form([Forms\Components\Textarea::make('note')->label('Note (optional)')->maxLength(255)->placeholder('e.g. Confirmed with the student, wrong amount keyed')])
            ->action(fn (array $arguments, array $data) => $this->review(
                fn () => app(PocketPayService::class)->approveReversal($this->sale($arguments), Auth::user(), $data['note'] ?? null),
                'Reversal approved. Card and float refunded.',
            ));
    }

    public function rejectReversalAction(): Action
    {
        return Action::make('rejectReversal')
            ->label('Reject')
            ->visible(fn (): bool => PocketPayAccess::canReviewReversals())
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->size('sm')
            ->form([Forms\Components\Textarea::make('note')->label('Reason')->required()->maxLength(255)->placeholder('e.g. The student received the item')])
            ->action(fn (array $arguments, array $data) => $this->review(
                fn () => app(PocketPayService::class)->rejectReversal($this->sale($arguments), Auth::user(), $data['note']),
                'Reversal rejected. The sale stands.',
            ));
    }

    // ------------------------------------------------------------------

    protected function attendant(array $arguments): CanteenAttendant
    {
        return CanteenAttendant::query()
            ->where('canteen_id', $this->canteen->id)
            ->findOrFail($arguments['attendant'] ?? null);
    }

    protected function sale(array $arguments): CanteenSale
    {
        return CanteenSale::query()
            ->where('canteen_id', $this->canteen->id)
            ->findOrFail($arguments['sale'] ?? null);
    }

    protected function card(array $arguments): PocketPayCard
    {
        return PocketPayCard::query()
            ->where('business_id', $this->canteen->business_id)
            ->findOrFail($arguments['card'] ?? null);
    }

    protected function review(callable $callback, string $success): void
    {
        abort_unless(PocketPayAccess::canReviewReversals(), 403);

        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (PocketPayException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function sendOtp(CanteenAttendant $attendant, string $title): void
    {
        if (! app(CanteenAttendantAuthService::class)->sendOtp($attendant)) {
            Notification::make()->title('A PIN was sent less than a minute ago. Try again shortly.')->warning()->send();

            return;
        }

        $sms = SmsMessage::query()
            ->where('purpose', 'pocket_pay.attendant_otp')
            ->where('related_type', CanteenAttendant::class)
            ->where('related_id', $attendant->id)
            ->latest('id')
            ->first();

        if ($sms && $sms->status !== 'sent') {
            Notification::make()
                ->title($title.', but the SMS was not sent')
                ->body($sms->error ?: 'Check MarzSMS settings and balance.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()->title($title)->body("One-time PIN sent to {$attendant->phone}.")->success()->send();
    }

    protected function syncPeriod(): void
    {
        [$from, $to] = $this->reports()->period($this->canteen, $this->from, $this->to);
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        unset($this->period, $this->report);
    }

    protected function reports(): CanteenReportService
    {
        return app(CanteenReportService::class);
    }
}
