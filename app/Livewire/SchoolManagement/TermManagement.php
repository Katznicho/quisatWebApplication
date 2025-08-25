<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Term;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
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

class TermManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Term::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academic_year')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_weeks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'upcoming',
                        'info' => 'completed',
                    ]),
                Tables\Columns\IconColumn::make('is_current')
                    ->boolean(),
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
                    ->modalHeading('Edit Term')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter term name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter term code'),
                        TextInput::make('academic_year')
                            ->required()
                            ->placeholder('Enter academic year'),
                        DatePicker::make('start_date')
                            ->required(),
                        DatePicker::make('end_date')
                            ->required(),
                        TextInput::make('duration_weeks')
                            ->numeric()
                            ->placeholder('Enter duration in weeks'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'upcoming' => 'Upcoming',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Toggle::make('is_current')
                            ->label('Current Term')
                            ->default(false),
                    ])
                    ->successNotificationTitle('Term updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Term')
                    ->successNotificationTitle('Term deleted successfully (soft).'),
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
                    ->label('Add Term')
                    ->modalHeading('Add New Term')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter term name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter term code'),
                        TextInput::make('academic_year')
                            ->required()
                            ->placeholder('Enter academic year'),
                        DatePicker::make('start_date')
                            ->required(),
                        DatePicker::make('end_date')
                            ->required(),
                        TextInput::make('duration_weeks')
                            ->numeric()
                            ->placeholder('Enter duration in weeks'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'upcoming' => 'Upcoming',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Toggle::make('is_current')
                            ->label('Current Term')
                            ->default(false),
                    ])
                    ->createAnother(false)
                    ->after(function (Term $record) {
                        Notification::make()
                            ->title('Term created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.term-management');
    }
}
