<?php

namespace App\Livewire;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class BusinessesTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public function table(Table $table): Table
    {
        // Only allow super admin (business_id = 1) to access
        if (!Auth::check() || Auth::user()->business_id !== 1) {
            abort(403, 'Unauthorized action.');
        }

        return $table
            ->query(fn (): Builder => Business::withCount('users'))
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-logo.png'))
                    ->url(function ($record) {
                        if (!$record->logo || $record->logo === 'logo.png') {
                            return asset('images/default-logo.png');
                        }
                        return asset('storage/' . $record->logo);
                    })
                    ->openUrlInNewTab(),
                
                TextColumn::make('name')
                    ->label('Business Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->url(fn ($record) => route('businesses.edit', $record->uuid))
                    ->color('primary'),
                
                TextColumn::make('account_balance')
                    ->label('Account Balance')
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . ' UGX')
                    ->sortable()
                    ->money('UGX', divideBy: 1),
                
                TextColumn::make('cost_per_sms')
                    ->label('Cost Per SMS')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' UGX')
                    ->sortable(),
                
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                // Actions removed
            ])
            ->filters([
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
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        return view('livewire.businesses-table');
    }
}