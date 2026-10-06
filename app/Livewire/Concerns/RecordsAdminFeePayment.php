<?php

namespace App\Livewire\Concerns;

use App\Models\Fee;
use App\Models\FeePayment;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;

trait RecordsAdminFeePayment
{
    protected function recordPaymentAction(): Action
    {
        return Action::make('record_payment')
            ->label('Record payment')
            ->icon('heroicon-o-banknotes')
            ->visible(fn (Fee $record) => $record->isPayable())
            ->modalHeading('Record payment and send receipt')
            ->modalDescription('Use this when the parent did not pay in the app. The payment option you choose is printed on their invoice, and they receive the receipt.')
            ->form([
                TextInput::make('amount')
                    ->label('Amount received')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(fn (Fee $record) => (int) round($record->remainingBalance())),
                Select::make('method')
                    ->label('Payment option')
                    ->options(collect(FeePayment::methodOptions())->only([
                        'mtn_mobile_money',
                        'airtel_money',
                        'card',
                        'cash',
                        'bank_transfer',
                        'other',
                    ])->all())
                    ->required()
                    ->native(false),
                FileUpload::make('proof')
                    ->label('Screenshot / proof of payment')
                    ->disk('public')
                    ->directory('fee-payment-proofs')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'])
                    ->maxSize(10240)
                    ->required()
                    ->helperText('Required. The parent receives a receipt after this is saved.'),
                Textarea::make('notes')
                    ->rows(2)
                    ->placeholder('Optional note for the receipt'),
            ])
            ->action(function (Fee $record, array $data): void {
                $amount = min((float) $data['amount'], $record->remainingBalance());

                if ($amount < 1) {
                    Notification::make()
                        ->title('Enter an amount that is still due.')
                        ->danger()
                        ->send();

                    return;
                }

                $proofPath = $data['proof'] ?? null;
                if (is_array($proofPath)) {
                    $proofPath = $proofPath[0] ?? null;
                }

                $mime = null;
                if (is_string($proofPath) && $proofPath !== '' && Storage::disk('public')->exists($proofPath)) {
                    $mime = Storage::disk('public')->mimeType($proofPath);
                }

                $record->applyCompletedPayment($amount, (string) $data['method'], [
                    'notes' => filled($data['notes'] ?? null) ? $data['notes'] : 'Recorded by admin with payment proof.',
                    'proof_path' => $proofPath,
                    'proof_url' => $proofPath ? asset('storage/'.$proofPath) : null,
                    'proof_mime_type' => $mime,
                ]);

                Notification::make()
                    ->title('Payment recorded. The parent receipt has been sent.')
                    ->success()
                    ->send();
            });
    }
}
