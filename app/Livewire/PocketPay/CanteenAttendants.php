<?php

namespace App\Livewire\PocketPay;

use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\SmsMessage;
use App\Services\CanteenAttendantAuthService;
use App\Services\MarzSmsService;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CanteenAttendants extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Canteen $canteen;

    public function mount(Canteen $canteen): void
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($canteen);
        $this->canteen = $canteen;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CanteenAttendant::query()->where('canteen_id', $this->canteen->id))
            ->heading('Attendants')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('phone')->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title())
                    ->color(fn (string $state): string => match ($state) {
                        CanteenAttendant::STATUS_ACTIVE => 'success',
                        CanteenAttendant::STATUS_PENDING_SETUP => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_login_at')->label('Last login')->since()->placeholder('Never'),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Add attendant')
                    ->icon('heroicon-o-user-plus')
                    ->modalDescription('The attendant receives an SMS with a one-time PIN to set up their own 4-digit PIN in the Quisat Attendant app.')
                    ->form([
                        Forms\Components\TextInput::make('name')->required()->maxLength(120),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
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
                    }),
            ])
            ->actions([
                Action::make('send_otp')
                    ->label(fn (CanteenAttendant $record): string => $record->status === CanteenAttendant::STATUS_PENDING_SETUP ? 'Resend setup PIN' : 'Send PIN reset')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->visible(fn (CanteenAttendant $record): bool => $record->status !== CanteenAttendant::STATUS_DISABLED)
                    ->requiresConfirmation()
                    ->modalDescription('Send a one-time PIN by SMS. The attendant uses it in the app to set a new 4-digit PIN.')
                    ->action(fn (CanteenAttendant $record) => $this->sendOtp($record, 'One-time PIN')),
                Action::make('disable')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (CanteenAttendant $record): bool => $record->status !== CanteenAttendant::STATUS_DISABLED)
                    ->requiresConfirmation()
                    ->modalDescription('The attendant is signed out of the app and cannot log in until re-enabled.')
                    ->action(function (CanteenAttendant $record): void {
                        $record->update(['status' => CanteenAttendant::STATUS_DISABLED]);
                        $record->tokens()->delete();
                        Notification::make()->title('Attendant disabled')->success()->send();
                    }),
                Action::make('enable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CanteenAttendant $record): bool => $record->status === CanteenAttendant::STATUS_DISABLED)
                    ->requiresConfirmation()
                    ->action(function (CanteenAttendant $record): void {
                        $record->update([
                            'status' => $record->pin ? CanteenAttendant::STATUS_ACTIVE : CanteenAttendant::STATUS_PENDING_SETUP,
                        ]);
                        Notification::make()->title('Attendant enabled')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No attendants yet')
            ->emptyStateDescription('Add the people who will sell at this canteen.');
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

    public function render(): View
    {
        return view('livewire.pocket-pay.table');
    }
}
