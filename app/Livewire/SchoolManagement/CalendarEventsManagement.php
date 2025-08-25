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

class CalendarEventsManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(CalendarEvent::query())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'primary' => 'academic',
                        'success' => 'sports',
                        'warning' => 'cultural',
                        'danger' => 'holidays',
                        'info' => 'meetings',
                        'secondary' => 'exams',
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
                            ->placeholder('Enter event title'),
                        Textarea::make('description')
                            ->placeholder('Enter event description')
                            ->rows(3),
                        Select::make('category')
                            ->options([
                                'academic' => 'Academic',
                                'sports' => 'Sports',
                                'cultural' => 'Cultural',
                                'holidays' => 'Holidays',
                                'meetings' => 'Meetings',
                                'exams' => 'Exams',
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
                            ->placeholder('Enter event location'),
                        Toggle::make('is_all_day')
                            ->label('All Day Event'),
                    ])
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
                            ->placeholder('Enter event title'),
                        Textarea::make('description')
                            ->placeholder('Enter event description')
                            ->rows(3),
                        Select::make('category')
                            ->options([
                                'academic' => 'Academic',
                                'sports' => 'Sports',
                                'cultural' => 'Cultural',
                                'holidays' => 'Holidays',
                                'meetings' => 'Meetings',
                                'exams' => 'Exams',
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
                            ->placeholder('Enter event location'),
                        Toggle::make('is_all_day')
                            ->label('All Day Event'),
                    ])
                    ->createAnother(false)
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
