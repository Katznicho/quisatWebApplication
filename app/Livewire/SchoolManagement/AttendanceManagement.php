<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Attendance;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
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

class AttendanceManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Attendance::query())
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.grade.name')
                    ->label('Grade')
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.class_room.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                    ]),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_out')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Attendance')
                    ->form([
                        Select::make('student_id')
                            ->relationship('student', 'first_name')
                            ->label('Student')
                            ->required(),
                        DatePicker::make('date')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                            ])
                            ->required(),
                        TimePicker::make('time_in')
                            ->label('Time In'),
                        TimePicker::make('time_out')
                            ->label('Time Out'),
                        Textarea::make('notes')
                            ->placeholder('Enter notes')
                            ->rows(3),
                    ])
                    ->successNotificationTitle('Attendance updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Attendance')
                    ->successNotificationTitle('Attendance deleted successfully (soft).'),
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
                    ->label('Add Attendance')
                    ->modalHeading('Add New Attendance Record')
                    ->form([
                        Select::make('student_id')
                            ->relationship('student', 'first_name')
                            ->label('Student')
                            ->required(),
                        DatePicker::make('date')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                            ])
                            ->required(),
                        TimePicker::make('time_in')
                            ->label('Time In'),
                        TimePicker::make('time_out')
                            ->label('Time Out'),
                        Textarea::make('notes')
                            ->placeholder('Enter notes')
                            ->rows(3),
                    ])
                    ->createAnother(false)
                    ->after(function (Attendance $record) {
                        Notification::make()
                            ->title('Attendance recorded successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.attendance-management');
    }
}
