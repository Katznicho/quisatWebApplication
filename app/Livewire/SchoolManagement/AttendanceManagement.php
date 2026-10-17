<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Attendance;
use App\Models\ChurchService;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\PickupCodeService;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AttendanceManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use \App\Livewire\Concerns\DownloadsTableDocuments;

    public function table(Table $table): Table
    {
        $businessId = auth()->user()->business_id ?? null;
        $baseQuery = $businessId
            ? Attendance::query()->where('business_id', $businessId)
            : Attendance::query();
        $this->orderByClassName($baseQuery);

        return $table
            ->query($baseQuery)
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.classRoom.name')
                    ->label(auth()->user()?->business?->isChurch() ? 'Group' : 'Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_name')
                    ->label('Service')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: ! (auth()->user()?->business?->isChurch() ?? false)),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                    ]),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('attendance_date')
                    ->form([
                        DatePicker::make('on')->label('Date'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['on'] ?? null, fn (Builder $inner, $date) => $inner->whereDate('attendance_date', $date))),
                SelectFilter::make('church_service_id')
                    ->label('Service')
                    ->options(fn () => ChurchService::query()
                        ->where('business_id', auth()->user()->business_id)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->visible(fn (): bool => (bool) auth()->user()?->business?->isChurch()),
                SelectFilter::make('class_room_id')
                    ->label(auth()->user()?->business?->isChurch() ? 'Group' : 'Class')
                    ->options(fn () => ClassRoom::query()
                        ->where('business_id', auth()->user()->business_id)
                        ->orderBy('name')
                        ->pluck('name', 'id')),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Attendance')
                    ->form([
                        Select::make('student_id')
                            ->relationship('student', 'first_name', fn (Builder $q) => $businessId ? $q->where('business_id', $businessId) : $q)
                            ->label('Student')
                            ->required(),
                        DatePicker::make('attendance_date')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                                'excused' => 'Excused',
                                'sick' => 'Sick',
                            ])
                            ->required(),
                        TimePicker::make('check_in_time')
                            ->label('Time In'),
                        TimePicker::make('check_out_time')
                            ->label('Time Out'),
                        Textarea::make('remarks')
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
                ...$this->tableDocumentActions('Attendance'),
                CreateAction::make()
                    ->label('Add Attendance')
                    ->modalHeading('Add New Attendance Record')
                    ->form([
                        Select::make('student_id')
                            ->label('Student')
                            ->options(fn () => Student::when($businessId, fn (Builder $q) => $q->where('business_id', $businessId))->orderBy('first_name')->get()->mapWithKeys(fn ($s) => [$s->id => $s->first_name . ' ' . $s->last_name]))
                            ->required()
                            ->searchable(),
                        DatePicker::make('attendance_date')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                                'excused' => 'Excused',
                                'sick' => 'Sick',
                            ])
                            ->required(),
                        TimePicker::make('check_in_time')
                            ->label('Time In'),
                        TimePicker::make('check_out_time')
                            ->label('Time Out'),
                        Textarea::make('remarks')
                            ->placeholder('Enter notes')
                            ->rows(3),
                    ])
                    ->using(function (array $data): Attendance {
                        $student = Student::find($data['student_id']);
                        return Attendance::create([
                            'business_id' => $businessId ?? $student->business_id,
                            'student_id' => $data['student_id'],
                            'class_room_id' => $student->class_room_id,
                            'attendance_date' => $data['attendance_date'],
                            'status' => $data['status'],
                            'check_in_time' => $data['check_in_time'] ?? null,
                            'check_out_time' => $data['check_out_time'] ?? null,
                            'remarks' => $data['remarks'] ?? null,
                            'marked_by' => auth()->id(),
                        ]);
                    })
                    ->createAnother(false)
                    ->after(function (Attendance $record) {
                        if ($record->check_in_time || $record->status === 'present') {
                            app(PickupCodeService::class)->issueForAttendance($record);
                        }

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
