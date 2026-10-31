<?php

namespace App\Livewire\MarzSms;

use App\Models\Business;
use App\Models\SmsMessage;
use App\Services\MarzSmsService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListSmsMessages extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SmsMessage::query()
                    ->with(['business:id,name', 'sender:id,name'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient')
                    ->label('Recipient')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (SmsMessage $record): string => $record->message)
                    ->wrap(),
                Tables\Columns\TextColumn::make('purpose')
                    ->label('Purpose')
                    ->formatStateUsing(fn (string $state): string => str($state)->replace(['.', '_'], ' ')->title())
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('School / Business')
                    ->placeholder('Quisat')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Cost (UGX)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'gray',
                        default => 'warning',
                    })
                    ->tooltip(fn (SmsMessage $record): ?string => $record->error)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sent by')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('provider_transaction_id')
                    ->label('MarzSMS ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'skipped' => 'Skipped',
                    ]),
                Tables\Filters\SelectFilter::make('purpose')
                    ->options(fn (): array => SmsMessage::query()
                        ->distinct()
                        ->orderBy('purpose')
                        ->pluck('purpose', 'purpose')
                        ->all()),
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('School / Business')
                    ->searchable()
                    ->options(fn (): array => Business::query()
                        ->whereIn('id', SmsMessage::query()->whereNotNull('business_id')->distinct()->select('business_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('retry')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (SmsMessage $record): bool => in_array($record->status, ['failed', 'skipped'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Resend SMS')
                    ->modalDescription('Send this message again through MarzSMS. You will be charged for it.')
                    ->action(function (SmsMessage $record): void {
                        $record->update(['status' => 'pending', 'error' => null]);
                        $sms = app(MarzSmsService::class)->deliver($record);

                        if ($sms->status === 'sent') {
                            Notification::make()->title('SMS sent.')->success()->send();

                            return;
                        }

                        Notification::make()->title('SMS failed: '.$sms->error)->danger()->send();
                    }),
            ])
            ->emptyStateHeading('No SMS sent yet')
            ->emptyStateDescription('Messages sent through MarzSMS will appear here.');
    }

    public function render(): View
    {
        return view('livewire.marz-sms.list-sms-messages');
    }
}
