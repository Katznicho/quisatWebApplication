<?php

namespace App\Livewire\PocketPay;

use App\Models\Canteen;
use App\Models\CanteenFloatTransaction;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CanteenFloatStatement extends Component implements HasForms, HasTable
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
            ->query(CanteenFloatTransaction::query()->where('canteen_id', $this->canteen->id))
            ->heading('Float statement')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('M d, Y H:i'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',
                        'reversal' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')->wrap(),
                Tables\Columns\TextColumn::make('debit')
                    ->label('Out (UGX)')
                    ->state(fn (CanteenFloatTransaction $record) => $record->direction === 'debit' ? $record->amount : null)
                    ->numeric()
                    ->placeholder('')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('credit')
                    ->label('In (UGX)')
                    ->state(fn (CanteenFloatTransaction $record) => $record->direction === 'credit' ? $record->amount : null)
                    ->numeric()
                    ->placeholder('')
                    ->color('success'),
                Tables\Columns\TextColumn::make('balance_after')->label('Balance (UGX)')->numeric()->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'topup' => 'Top-up',
                    'sale' => 'Sale',
                    'reversal' => 'Reversal',
                ]),
            ])
            ->emptyStateHeading('No float movements yet');
    }

    public function render(): View
    {
        return view('livewire.pocket-pay.table');
    }
}
