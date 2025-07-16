<?php

namespace App\Livewire\Transactions;

use App\Models\Transaction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListTransactions extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        // $query = Transaction::query()
        $query = Transaction::latest(); // Orders by created_at DESC by default

         //get the lastest transactions

        ;

        // If not admin, limit to their business_id
        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->sortable()
                    ->money('UGX')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->label('Payment Reference')

                    ,

                Tables\Columns\TextColumn::make('description')
                    ->limit(40),

                Tables\Columns\TextColumn::make('transaction_for')
                    ->label('Transaction For')
                    ->sortable(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),


                Auth::user()?->business_id == 1
                    ? Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'processing' => 'Processing',
                    ])
                    ->label('Status')
                    ->sortable()
                    : Tables\Columns\TextColumn::make('status')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('origin')
                    ->sortable()
                    ->visible(Auth::user()?->business_id === 1)
                    ,

                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('provider')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service'),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency'),

                Tables\Columns\TextColumn::make('names'),

                Tables\Columns\TextColumn::make('email'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable()
                    ->visible(Auth::user()?->business_id === 1)
                    ,

                Tables\Columns\TextColumn::make('user_agent')
                    ->toggleable()
                    ->visible(Auth::user()?->business_id === 1)
                    ,

                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('deleted_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'processing' => 'Processing',
                    ])
            ])
            ->actions([
                // Optional actions
                Tables\Actions\ViewAction::make()
                    ->url(fn(Transaction $record): string => route('transactions.show', $record))

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Optional bulk actions
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.transactions.list-transactions');
    }
}
