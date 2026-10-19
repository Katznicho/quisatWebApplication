<?php

namespace App\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\KcbTransaction;

class KcbTransactionsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KcbTransaction::query())
            ->columns([
                TextColumn::make('request_id')
                    ->label('Request ID')
                    ->searchable()
                    ->limit(20)
                    ->copyable()
                    ->fontFamily('mono'),
                
                TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'validation' => 'info',
                        'notification' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                TextColumn::make('customer_reference')
                    ->label('Customer Reference')
                    ->searchable()
                    ->placeholder('-'),
                
                TextColumn::make('transaction_reference')
                    ->label('Transaction Reference')
                    ->searchable()
                    ->placeholder('-'),
                
                TextColumn::make('transaction_amount')
                    ->label('Amount')
                    ->money('KES', divideBy: 1)
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return number_format($state, 2) . ' ' . ($record->currency ?? 'KES');
                        }
                        if ($record->bill_amount) {
                            return number_format($record->bill_amount, 2) . ' ' . ($record->currency ?? 'KES');
                        }
                        return '-';
                    })
                    ->sortable(),
                
                TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === '0' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === '0' ? 'Success' : 'Failed'),
                
                TextColumn::make('transaction_timestamp')
                    ->label('Transaction Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->default(fn ($record) => $record->created_at),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Type')
                    ->options([
                        'validation' => 'Validation',
                        'notification' => 'Notification',
                    ]),
                
                SelectFilter::make('status_code')
                    ->label('Status')
                    ->options([
                        '0' => 'Success',
                        '1' => 'Failed',
                    ]),
                
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From Date'),
                        DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (KcbTransaction $record): string => route('kcb-transactions.show', $record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        return view('livewire.kcb-transactions-table');
    }
}
