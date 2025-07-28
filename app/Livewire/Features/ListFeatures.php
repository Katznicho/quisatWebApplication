<?php

namespace App\Livewire\Features;

use App\Models\Feature;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Currency;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;

class ListFeatures extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Feature::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label('Currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(function ($record) {
                        $currency = $record->currency;
                        $symbol = $currency->symbol;
                        $position = $currency->position;
                        switch ($position) {
                            case 'left':
                                return $symbol . $record->price;
                            case 'right':
                                return $record->price . $symbol;
                            case 'left_with_space':
                                return $symbol . ' ' . $record->price;
                            case 'right_with_space':
                                return $record->price . ' ' . $symbol;
                            default:
                                return $record->price;
                        }
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Feature')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter feature name'),
                        Textarea::make('description')
                            ->nullable()
                            ->placeholder('Enter feature description'),
                        Select::make('currency_id')
                            ->label('Currency')
                            ->options(Currency::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select currency'),
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter price'),
                    ])
                    ->successNotificationTitle('Feature updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Feature')
                    ->successNotificationTitle('Feature deleted successfully (soft).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create Feature')
                    ->modalHeading('Add New Feature')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter feature name'),
                        Textarea::make('description')
                            ->nullable()
                            ->placeholder('Enter feature description'),
                        Select::make('currency_id')
                            ->label('Currency')
                            ->options(Currency::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select currency'),
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter price'),
                    ])
                    ->createAnother(false)
                    ->after(function (Feature $record) {
                        Notification::make()
                            ->title('Feature created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.features.list-features');
    }
}
