<?php

namespace App\Livewire\Programs;

use App\Models\Program;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListPrograms extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Program::query())
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('age-group')
                    ->label('Age Group')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        default => 'gray',
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->disabled(),
                        \Filament\Forms\Components\Textarea::make('description')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('age-group')
                            ->disabled(),
                        \Filament\Forms\Components\Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->disabled(),
                    ]),
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('Enter program name'),
                        \Filament\Forms\Components\Textarea::make('description')
                            ->placeholder('Enter description'),
                        \Filament\Forms\Components\TextInput::make('age-group')
                            ->placeholder('Enter age group'),
                        \Filament\Forms\Components\Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->required()
                            ->default('active'),
                    ]),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('Enter program name'),
                        \Filament\Forms\Components\Textarea::make('description')
                            ->placeholder('Enter description'),
                        \Filament\Forms\Components\TextInput::make('age-group')
                            ->placeholder('Enter age group'),
                        \Filament\Forms\Components\Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->required()
                            ->default('active'),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.programs.list-programs');
    }
}
