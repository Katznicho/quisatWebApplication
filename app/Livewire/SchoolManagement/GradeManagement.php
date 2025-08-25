<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Grade;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
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

class GradeManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Grade::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('age_range')
                    ->searchable(),
                Tables\Columns\TextColumn::make('academic_year')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'pending',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
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
                    ->modalHeading('Edit Grade')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter grade name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter grade code'),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        TextInput::make('level')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter grade level'),
                        TextInput::make('age_range')
                            ->placeholder('Enter age range (e.g., 6-7 years)'),
                        TextInput::make('academic_year')
                            ->placeholder('Enter academic year'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'pending' => 'Pending',
                            ])
                            ->default('active'),
                        ColorPicker::make('color')
                            ->label('Grade Color'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->successNotificationTitle('Grade updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Grade')
                    ->successNotificationTitle('Grade deleted successfully (soft).'),
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
                    ->label('Add Grade')
                    ->modalHeading('Add New Grade')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter grade name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter grade code'),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        TextInput::make('level')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter grade level'),
                        TextInput::make('age_range')
                            ->placeholder('Enter age range (e.g., 6-7 years)'),
                        TextInput::make('academic_year')
                            ->placeholder('Enter academic year'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'pending' => 'Pending',
                            ])
                            ->default('active'),
                        ColorPicker::make('color')
                            ->label('Grade Color'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->createAnother(false)
                    ->after(function (Grade $record) {
                        Notification::make()
                            ->title('Grade created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.grade-management');
    }
}
