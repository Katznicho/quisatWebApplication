<?php

namespace App\Livewire\SchoolManagement;

use App\Models\CalendarEvent;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
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
use Illuminate\Support\Facades\Auth;

class CalendarEventsManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(CalendarEvent::query()->where('business_id', Auth::user()->business_id))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->colors([
                        'primary' => 'meeting',
                        'success' => 'class',
                        'warning' => 'exam',
                        'danger' => 'holiday',
                        'info' => 'activity',
                        'secondary' => 'other',
                    ]),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_all_day')
                    ->boolean(),
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
                    ->modalHeading('Edit Event')
                    ->form([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter event title'),
                        Textarea::make('description')
                            ->placeholder('Enter event description')
                            ->rows(3),
                        Select::make('event_type')
                            ->options([
                                'meeting' => 'Meeting',
                                'class' => 'Class',
                                'exam' => 'Exam',
                                'holiday' => 'Holiday',
                                'activity' => 'Activity',
                                'other' => 'Other',
                            ])
                            ->required(),
                        DatePicker::make('start_date')
                            ->required(),
                        TimePicker::make('start_time')
                            ->label('Start Time'),
                        DatePicker::make('end_date')
                            ->required(),
                        TimePicker::make('end_time')
                            ->label('End Time'),
                        TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Enter event location'),
                        Toggle::make('is_all_day')
                            ->label('All Day Event'),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium')
                            ->required(),
                    ])
                    ->using(function (array $data, CalendarEvent $record): CalendarEvent {
                        $data['business_id'] = Auth::user()->business_id;
                        $data['created_by'] = Auth::id();
                        
                        // Combine date and time for start_date
                        if (isset($data['start_date'])) {
                            if (isset($data['start_time'])) {
                                $data['start_date'] = $data['start_date'] . ' ' . $data['start_time'];
                                unset($data['start_time']);
                            } else {
                                $data['start_date'] = $data['start_date'] . ' 00:00:00';
                            }
                        }
                        
                        // Combine date and time for end_date
                        if (isset($data['end_date'])) {
                            if (isset($data['end_time'])) {
                                $data['end_date'] = $data['end_date'] . ' ' . $data['end_time'];
                                unset($data['end_time']);
                            } else {
                                $data['end_date'] = $data['end_date'] . ' 23:59:59';
                            }
                        }
                        
                        $record->fill($data);
                        $record->save();
                        
                        return $record;
                    })
                    ->successNotificationTitle('Event updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Event')
                    ->successNotificationTitle('Event deleted successfully (soft).'),
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
                    ->label('Add Event')
                    ->modalHeading('Add New Event')
                    ->form([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter event title'),
                        Textarea::make('description')
                            ->placeholder('Enter event description')
                            ->rows(3),
                        Select::make('event_type')
                            ->options([
                                'meeting' => 'Meeting',
                                'class' => 'Class',
                                'exam' => 'Exam',
                                'holiday' => 'Holiday',
                                'activity' => 'Activity',
                                'other' => 'Other',
                            ])
                            ->required(),
                        DatePicker::make('start_date')
                            ->required(),
                        TimePicker::make('start_time')
                            ->label('Start Time'),
                        DatePicker::make('end_date')
                            ->required(),
                        TimePicker::make('end_time')
                            ->label('End Time'),
                        TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Enter event location'),
                        Toggle::make('is_all_day')
                            ->label('All Day Event'),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium')
                            ->required(),
                    ])
                    ->createAnother(false)
                    ->using(function (array $data): CalendarEvent {
                        $data['business_id'] = Auth::user()->business_id;
                        $data['created_by'] = Auth::id();
                        $data['status'] = 'published';
                        
                        // Combine date and time for start_date
                        if (isset($data['start_date'])) {
                            if (isset($data['start_time'])) {
                                $data['start_date'] = $data['start_date'] . ' ' . $data['start_time'];
                                unset($data['start_time']);
                            } else {
                                $data['start_date'] = $data['start_date'] . ' 00:00:00';
                            }
                        }
                        
                        // Combine date and time for end_date
                        if (isset($data['end_date'])) {
                            if (isset($data['end_time'])) {
                                $data['end_date'] = $data['end_date'] . ' ' . $data['end_time'];
                                unset($data['end_time']);
                            } else {
                                $data['end_date'] = $data['end_date'] . ' 23:59:59';
                            }
                        }
                        
                        return CalendarEvent::create($data);
                    })
                    ->after(function (CalendarEvent $record) {
                        Notification::make()
                            ->title('Event created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.calendar-events-management');
    }
}
