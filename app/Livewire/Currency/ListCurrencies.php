<?php

namespace App\Livewire\Currency;

use App\Models\Currency;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListCurrencies extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Currency::query())
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rate')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('position'),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Currency')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter currency name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter currency code (e.g., USD)'),
                        TextInput::make('symbol')
                            ->required()
                            ->placeholder('Enter currency symbol (e.g., $)'),
                        TextInput::make('rate')
                            ->numeric()
                            ->placeholder('Enter exchange rate'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active'),
                        Select::make('position')
                            ->options([
                                'left' => 'Left',
                                'right' => 'Right',
                                'left_with_space' => 'Left with space',
                                'right_with_space' => 'Right with space',
                            ])
                            ->default('left'),
                        Toggle::make('is_default')
                            ->default(false),
                    ])
                    ->successNotificationTitle('Currency updated successfully.')
                    ->after(function (Currency $record) {
                        if ($record->is_default) {
                            Currency::where('id', '!=', $record->id)->update(['is_default' => false]);
                        }
                    }),
                DeleteAction::make()
                    ->modalHeading('Delete Currency')
                    ->successNotificationTitle('Currency deleted successfully (soft).'),
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
                    ->label('Create Currency')
                    ->modalHeading('Add New Currency')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter currency name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter currency code (e.g., USD)'),
                        TextInput::make('symbol')
                            ->required()
                            ->placeholder('Enter currency symbol (e.g., $)'),
                        TextInput::make('rate')
                            ->numeric()
                            ->placeholder('Enter exchange rate'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active'),
                        Select::make('position')
                            ->options([
                                'left' => 'Left',
                                'right' => 'Right',
                                'left_with_space' => 'Left with space',
                                'right_with_space' => 'Right with space',
                            ])
                            ->default('left'),
                        Toggle::make('is_default')
                            ->default(false),
                    ])
                    ->createAnother(false)
                    ->after(function (Currency $record) {
                        if ($record->is_default) {
                            Currency::where('id', '!=', $record->id)->update(['is_default' => false]);
                        }
                        Notification::make()
                            ->title('Currency created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.currency.list-currencies');
    }
}
