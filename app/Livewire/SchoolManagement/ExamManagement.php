<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Exam;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
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

class ExamManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = Exam::query();
        
        // Filter by business_id for non-admin users
        if (auth()->user()->business_id !== 1) {
            $query->where('business_id', auth()->user()->business_id);
        }
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),
                Tables\Columns\TextColumn::make('class_room.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_marks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('passing_marks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('exam_type')
                    ->colors([
                        'primary' => 'midterm',
                        'success' => 'final',
                        'warning' => 'quiz',
                        'danger' => 'assignment',
                        'info' => 'project',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => 'scheduled',
                        'warning' => 'ongoing',
                        'success' => 'completed',
                        'danger' => 'cancelled',
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
                    ->modalHeading('Edit Exam')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter exam name'),
                        Textarea::make('description')
                            ->placeholder('Enter exam description')
                            ->rows(3),
                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Subject')
                            ->required(),
                        Select::make('class_room_id')
                            ->relationship('classRoom', 'name')
                            ->label('Class')
                            ->required(),
                        DatePicker::make('exam_date')
                            ->required(),
                        TimePicker::make('start_time')
                            ->required(),
                        TimePicker::make('end_time')
                            ->required(),
                        TextInput::make('total_marks')
                            ->numeric()
                            ->required()
                            ->placeholder('Total marks'),
                        TextInput::make('passing_marks')
                            ->numeric()
                            ->required()
                            ->placeholder('Passing marks'),
                        Select::make('exam_type')
                            ->options([
                                'midterm' => 'Midterm',
                                'final' => 'Final',
                                'quiz' => 'Quiz',
                                'assignment' => 'Assignment',
                                'project' => 'Project',
                            ])
                            ->required(),
                        Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('scheduled')
                            ->required(),
                    ])
                    ->successNotificationTitle('Exam updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Exam')
                    ->successNotificationTitle('Exam deleted successfully (soft).'),
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
                    ->label('Add Exam')
                    ->modalHeading('Add New Exam')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter exam name'),
                        Textarea::make('description')
                            ->placeholder('Enter exam description')
                            ->rows(3),
                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Subject')
                            ->required(),
                        Select::make('class_room_id')
                            ->relationship('classRoom', 'name')
                            ->label('Class')
                            ->required(),
                        DatePicker::make('exam_date')
                            ->required(),
                        TimePicker::make('start_time')
                            ->required(),
                        TimePicker::make('end_time')
                            ->required(),
                        TextInput::make('total_marks')
                            ->numeric()
                            ->required()
                            ->placeholder('Total marks'),
                        TextInput::make('passing_marks')
                            ->numeric()
                            ->required()
                            ->placeholder('Passing marks'),
                        Select::make('exam_type')
                            ->options([
                                'midterm' => 'Midterm',
                                'final' => 'Final',
                                'quiz' => 'Quiz',
                                'assignment' => 'Assignment',
                                'project' => 'Project',
                            ])
                            ->required(),
                        Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('scheduled')
                            ->required(),
                    ])
                    ->createAnother(false)
                    ->after(function (Exam $record) {
                        Notification::make()
                            ->title('Exam created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.exam-management');
    }
}
