<?php

namespace App\Livewire\MarzPay;

use App\Models\PaymentCollection;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListPaymentCollections extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PaymentCollection::query()
                    ->with('payable')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('payable_label')
                    ->label('For')
                    ->state(fn (PaymentCollection $record): string => $this->payableLabel($record))
                    ->wrap(),
                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Base (UGX)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform_charge')
                    ->label('Quisat Charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total Prompted (UGX)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'card' => 'Card',
                        'mobile_money' => 'Mobile Money',
                        default => ucfirst((string) $state),
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('provider_transaction_id')
                    ->label('Provider Txn ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed', 'cancelled' => 'danger',
                        'processing', 'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'mobile_money' => 'Mobile Money',
                        'card' => 'Card',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No MarzPay transactions yet')
            ->emptyStateDescription('Mobile money and card collections will appear here once customers pay online.');
    }

    protected function payableLabel(PaymentCollection $record): string
    {
        $payable = $record->payable;

        if (! $payable) {
            return class_basename((string) $record->payable_type).' #'.$record->payable_id;
        }

        return match ($payable::class) {
            \App\Models\Order::class => 'Order '.$payable->order_number,
            \App\Models\KidsEventRegistration::class => 'Kids Event: '.$payable->child_name,
            \App\Models\ParentCornerRegistration::class => 'Parent Corner: '.$payable->parent_name,
            \App\Models\EventAttendee::class => 'Program: '.$payable->child_name,
            default => class_basename($payable::class).' #'.$payable->getKey(),
        };
    }

    public function render(): View
    {
        return view('livewire.marz-pay.list-payment-collections');
    }
}
