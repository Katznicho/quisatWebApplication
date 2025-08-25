<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Fee;
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

class FeeManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Fee::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('grade.name')
                    ->label('Grade')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'tuition',
                        'success' => 'transport',
                        'warning' => 'library',
                        'danger' => 'laboratory',
                        'info' => 'sports',
                        'secondary' => 'other',
                    ]),
                Tables\Columns\BadgeColumn::make('frequency')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'quarterly',
                        'warning' => 'semester',
                        'danger' => 'annual',
                        'info' => 'one-time',
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
                    ->modalHeading('Edit Fee')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter fee name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter fee code'),
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter amount'),
                        Select::make('grade_id')
                            ->relationship('grade', 'name')
                            ->label('Grade')
                            ->required(),
                        DatePicker::make('due_date')
                            ->required(),
                        Select::make('type')
                            ->options([
                                'tuition' => 'Tuition Fee',
                                'transport' => 'Transport Fee',
                                'library' => 'Library Fee',
                                'laboratory' => 'Laboratory Fee',
                                'sports' => 'Sports Fee',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Select::make('frequency')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semester' => 'Semester',
                                'annual' => 'Annual',
                                'one-time' => 'One Time',
                            ])
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->successNotificationTitle('Fee updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Fee')
                    ->successNotificationTitle('Fee deleted successfully (soft).'),
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
                    ->label('Add Fee')
                    ->modalHeading('Add New Fee')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter fee name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter fee code'),
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter amount'),
                        Select::make('grade_id')
                            ->relationship('grade', 'name')
                            ->label('Grade')
                            ->required(),
                        DatePicker::make('due_date')
                            ->required(),
                        Select::make('type')
                            ->options([
                                'tuition' => 'Tuition Fee',
                                'transport' => 'Transport Fee',
                                'library' => 'Library Fee',
                                'laboratory' => 'Laboratory Fee',
                                'sports' => 'Sports Fee',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Select::make('frequency')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semester' => 'Semester',
                                'annual' => 'Annual',
                                'one-time' => 'One Time',
                            ])
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->createAnother(false)
                    ->after(function (Fee $record) {
                        Notification::make()
                            ->title('Fee created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.fee-management');
    }
}
