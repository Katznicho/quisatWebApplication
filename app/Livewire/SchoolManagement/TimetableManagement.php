<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Timetable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
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

class TimetableManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = Timetable::query();
        
        // Filter by business_id for non-admin users
        if (auth()->user()->business_id !== 1) {
            $query->where('business_id', auth()->user()->business_id);
        }
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),
                Tables\Columns\TextColumn::make('class_room.name')
                    ->label('Classroom')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('room_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
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
                    ->modalHeading('Edit Timetable')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        Select::make('day_of_week')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->required(),
                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Subject')
                            ->required(),
                        Select::make('class_room_id')
                            ->relationship('classRoom', 'name')
                            ->label('Classroom')
                            ->required(),
                        Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->label('Teacher')
                            ->required(),
                        TimePicker::make('start_time')
                            ->required(),
                        TimePicker::make('end_time')
                            ->required(),
                        TextInput::make('room_number')
                            ->placeholder('Enter room number'),
                        Textarea::make('notes')
                            ->placeholder('Enter notes')
                            ->rows(3),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->successNotificationTitle('Timetable updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Timetable')
                    ->successNotificationTitle('Timetable deleted successfully (soft).'),
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
                    ->label('Add Timetable')
                    ->modalHeading('Add New Timetable Entry')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        Select::make('day_of_week')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->required(),
                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Subject')
                            ->required(),
                        Select::make('class_room_id')
                            ->relationship('classRoom', 'name')
                            ->label('Classroom')
                            ->required(),
                        Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->label('Teacher')
                            ->required(),
                        TimePicker::make('start_time')
                            ->required(),
                        TimePicker::make('end_time')
                            ->required(),
                        TextInput::make('room_number')
                            ->placeholder('Enter room number'),
                        Textarea::make('notes')
                            ->placeholder('Enter notes')
                            ->rows(3),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->createAnother(false)
                    ->after(function (Timetable $record) {
                        Notification::make()
                            ->title('Timetable entry created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.timetable-management');
    }
}
