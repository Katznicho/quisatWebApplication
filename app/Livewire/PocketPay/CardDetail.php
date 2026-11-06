<?php

namespace App\Livewire\PocketPay;

use App\Exceptions\PocketPayException;
use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Services\CardReportService;
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
 * Full card page: who holds it, balance and limits, statement, purchases, top-ups,
 * SMS alerts sent to the parent and every admin action on the card.
 */
class CardDetail extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    public const TABS = ['overview', 'statement', 'purchases', 'topups', 'alerts'];

    public PocketPayCard $card;

    #[Url]
    public string $tab = 'overview';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    #[Url]
    public string $status = '';

    public function mount(PocketPayCard $card): void
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($card);

        $this->card = $card->load('business', 'student.classRoom', 'student.parentGuardian', 'assigner:id,name', 'activatedBy');

        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'overview';
        }

        $this->syncPeriod();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $this->status = '';
        $this->resetPage();
    }

    public function preset(string $preset): void
    {
        $today = CarbonImmutable::now($this->reports()->timezone($this->card))->startOfDay();

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

        if (in_array($property, ['from', 'to', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function period(): array
    {
        return $this->reports()->period($this->card, $this->from, $this->to);
    }

    #[Computed]
    public function report(): array
    {
        [$from, $to] = $this->period;

        return $this->reports()->build($this->card, $from, $to);
    }

    public function render(): View
    {
        [$from, $to] = $this->period;
        [$start, $end] = $this->reports()->bounds($from, $to);

        return view('livewire.pocket-pay.card-detail', [
            'report' => $this->report,
            'ledger' => $this->tab === 'statement' ? $this->reports()->ledgerQuery($this->card, $start, $end)->paginate(50) : null,
            'sales' => $this->tab === 'purchases'
                ? $this->reports()->salesQuery($this->card, $start, $end)
                    ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                    ->latest('id')
                    ->paginate(25)
                : null,
            'topups' => $this->tab === 'topups' ? $this->reports()->topupsQuery($this->card, $start, $end)->latest('id')->paginate(25) : null,
            'alerts' => $this->tab === 'alerts' ? $this->reports()->alertsQuery($this->card)->latest('id')->paginate(25) : null,
            'numbers' => $this->card->cardNumbers()->orderByDesc('assigned_at')->get(),
        ]);
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    public function limitAction(): Action
    {
        return Action::make('limit')
            ->label('Daily limit')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('gray')
            ->fillForm(fn (): array => ['daily_limit' => $this->card->daily_limit !== null ? (int) $this->card->daily_limit : null])
            ->modalHeading('Daily spending limit')
            ->modalDescription('The most the student can spend in one day across all canteens. The parent gets an SMS about the change.')
            ->form([
                Forms\Components\TextInput::make('daily_limit')
                    ->label('Limit (UGX)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(PocketPayService::MAX_TOPUP)
                    ->placeholder('No limit')
                    ->helperText('Leave empty for no limit.'),
            ])
            ->action(function (array $data): void {
                $limit = filled($data['daily_limit'] ?? null) ? (float) $data['daily_limit'] : null;
                if ($this->guard(fn () => app(PocketPayService::class)->setDailyLimit($this->card, $limit, Auth::user()))) {
                    Notification::make()->title($limit === null ? 'Daily limit removed' : 'Daily limit updated')->success()->send();
                }
            });
    }

    public function unlockAction(): Action
    {
        return Action::make('unlock')
            ->label('Unlock PIN')
            ->icon('heroicon-o-lock-open')
            ->color('warning')
            ->visible(fn (): bool => $this->card->isLocked())
            ->requiresConfirmation()
            ->modalHeading('Unlock card')
            ->modalDescription('The card was locked after too many wrong PINs. Unlocking lets the student try their current PIN again; it does not change the PIN.')
            ->action(function (): void {
                app(PocketPayService::class)->unlockCard($this->card, Auth::user());
                Notification::make()->title('Card unlocked')->success()->send();
            });
    }

    public function changeNumberAction(): Action
    {
        return Action::make('changeNumber')
            ->label('Change number')
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->visible(fn (): bool => PocketPayAccess::canAssignCards())
            ->modalDescription('Use this when a card is lost or replaced. The balance, PIN and history stay with the student; the old number stops working.')
            ->form([ListPocketPayCards::cardNumberField()])
            ->action(function (array $data, Action $action): void {
                abort_unless(PocketPayAccess::canAssignCards(), 403);

                if (! ListPocketPayCards::changeNumber($this->card, (int) $data['card_number_id'])) {
                    Notification::make()->title('That card number is no longer available. Pick another.')->danger()->send();
                    $action->halt();
                }

                Notification::make()->title('Card number updated')->success()->send();
            });
    }

    public function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn (): bool => $this->card->status !== PocketPayCard::STATUS_BLOCKED)
            ->requiresConfirmation()
            ->modalHeading('Deactivate card')
            ->modalDescription('A deactivated card cannot buy or be topped up until it is activated again. The balance is kept.')
            ->action(function (): void {
                app(PocketPayService::class)->setBlocked($this->card, true, Auth::user());
                Notification::make()->title('Card deactivated')->success()->send();
            });
    }

    public function activateAction(): Action
    {
        return Action::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (): bool => $this->card->status === PocketPayCard::STATUS_BLOCKED)
            ->requiresConfirmation()
            ->modalHeading('Activate card')
            ->modalDescription('The card can buy again. A card that never had a PIN set still needs one from the attendant or parent app.')
            ->action(function (): void {
                app(PocketPayService::class)->setBlocked($this->card, false, Auth::user());
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
            ->form([Forms\Components\Textarea::make('note')->label('Note (optional)')->maxLength(255)])
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
            ->form([Forms\Components\Textarea::make('note')->label('Reason')->required()->maxLength(255)])
            ->action(fn (array $arguments, array $data) => $this->review(
                fn () => app(PocketPayService::class)->rejectReversal($this->sale($arguments), Auth::user(), $data['note']),
                'Reversal rejected. The sale stands.',
            ));
    }

    // ------------------------------------------------------------------

    protected function sale(array $arguments): CanteenSale
    {
        return CanteenSale::query()
            ->where('pocket_pay_card_id', $this->card->id)
            ->findOrFail($arguments['sale'] ?? null);
    }

    protected function review(callable $callback, string $success): void
    {
        abort_unless(PocketPayAccess::canReviewReversals(), 403);

        if ($this->guard($callback)) {
            Notification::make()->title($success)->success()->send();
        }
    }

    /**
     * Run a service call and show its PocketPayException as a notification instead of a 500.
     */
    protected function guard(callable $callback): bool
    {
        try {
            $callback();
            $this->card->refresh();
            unset($this->report);

            return true;
        } catch (PocketPayException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return false;
        }
    }

    protected function syncPeriod(): void
    {
        [$from, $to] = $this->reports()->period($this->card, $this->from, $this->to);
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        unset($this->period, $this->report);
    }

    protected function reports(): CardReportService
    {
        return app(CardReportService::class);
    }
}
