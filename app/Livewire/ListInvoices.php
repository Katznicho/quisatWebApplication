<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Services\CustomerSoapClient;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListInvoices extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query())
            ->defaultSort('postingDate', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoiceNo')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->url(fn (Invoice $record): string => route('invoices.view', $record->invoiceNo))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('postingDate')
                    ->label('Posting Date')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('startingDate')
                    ->label('Period From')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('endingDate')
                    ->label('Period To')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('salesAmount')
                    ->label('Sales Amount')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
                Tables\Columns\TextColumn::make('discountAmount')
                    ->label('Discount')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
                Tables\Columns\TextColumn::make('totalAmount')
                    ->label('Total Amount')
                    ->alignEnd()
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Invoice $record): string => route('invoices.view', $record->invoiceNo))
                    ->openUrlInNewTab(),
                Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->schema([
                        TextInput::make('email')
                            ->label('Send to email')
                            ->email()
                            ->required()
                            ->default(fn (): ?string => session('email')),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        try {
                            $result = app(CustomerSoapClient::class)->call('SendCustomerInvoice', [
                                'invoiceNo' => $record->invoiceNo,
                                'sendToEmail' => trim($data['email']),
                            ]);

                            $success = $result !== false
                                && $result !== null
                                && strtolower((string) $result) !== 'false';

                            if (! $success) {
                                Notification::make()
                                    ->title('Failed to send invoice')
                                    ->body(is_string($result) && $result !== '' ? $result : 'The service rejected the request.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Invoice sent')
                                ->body('Sent to '.$data['email'])
                                ->success()
                                ->send();
                        } catch (\Throwable $th) {
                            Notification::make()
                                ->title('Failed to send invoice')
                                ->body($th->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->filters([])
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No invoices found')
            ->emptyStateDescription('There are no invoices for this customer yet.');
    }

    public function render(): View
    {
        return view('livewire.list-invoices');
    }
}
