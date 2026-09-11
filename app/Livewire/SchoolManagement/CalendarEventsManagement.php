<?php

namespace App\Livewire\SchoolManagement;

use App\Models\CalendarEvent;
use App\Services\CalendarEventNotificationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

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
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => $state === null || (float) $state <= 0
                        ? 'Free'
                        : 'UGX '.number_format((float) $state, 0))
                    ->sortable(),
                Tables\Columns\IconColumn::make('accepts_registrations')
                    ->label('Register')
                    ->boolean(),
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
                            ->label('Start Time')
                            ->seconds(false),
                        DatePicker::make('end_date')
                            ->required(),
                        TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false),
                        TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Enter event location'),
                        TextInput::make('price')
                            ->label('Registration fee (UGX)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Set 0 for free events. Parents pay via MarzPay when registering.'),
                        Toggle::make('accepts_registrations')
                            ->label('Accept registrations in the app')
                            ->default(true),
                        TextInput::make('max_participants')
                            ->label('Max participants')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited.'),
                        FileUpload::make('cover_image')
                            ->label('Event cover image')
                            ->image()
                            ->directory('calendar-events')
                            ->disk('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->maxSize(2048)
                            ->helperText('Upload a photo that matches this event. Shown on the app.'),
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
                    ->mutateRecordDataUsing(fn (array $data): array => $this->splitEventDateTimes($data))
                    ->using(function (array $data, CalendarEvent $record): CalendarEvent {
                        $data['business_id'] = Auth::user()->business_id;
                        $data['created_by'] = Auth::id();
                        $data = $this->combineEventDateTimes($data);

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
                            ->label('Start Time')
                            ->seconds(false),
                        DatePicker::make('end_date')
                            ->required(),
                        TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false),
                        TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Enter event location'),
                        TextInput::make('price')
                            ->label('Registration fee (UGX)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Set 0 for free events. Parents pay via MarzPay when registering.'),
                        Toggle::make('accepts_registrations')
                            ->label('Accept registrations in the app')
                            ->default(true),
                        TextInput::make('max_participants')
                            ->label('Max participants')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited.'),
                        FileUpload::make('cover_image')
                            ->label('Event cover image')
                            ->image()
                            ->directory('calendar-events')
                            ->disk('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->maxSize(2048)
                            ->helperText('Upload a photo that matches this event. Shown on the app.'),
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
                        $data = $this->combineEventDateTimes($data);

                        return CalendarEvent::create($data);
                    })
                    ->after(function (CalendarEvent $record) {
                        try {
                            app(CalendarEventNotificationService::class)->notifyPublished($record);
                        } catch (\Throwable $e) {
                            report($e);
                        }

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

    /**
     * Split stored datetimes into date + time form fields so edits keep the original times.
     */
    private function splitEventDateTimes(array $data): array
    {
        if (! empty($data['start_date'])) {
            $start = Carbon::parse($data['start_date']);
            $data['start_date'] = $start->toDateString();
            $data['start_time'] = $start->format('H:i');
        }

        if (! empty($data['end_date'])) {
            $end = Carbon::parse($data['end_date']);
            $data['end_date'] = $end->toDateString();
            $data['end_time'] = $end->format('H:i');
        }

        return $data;
    }

    /**
     * Merge date and time picker values into the datetime columns.
     */
    private function combineEventDateTimes(array $data): array
    {
        if (array_key_exists('start_date', $data)) {
            $data['start_date'] = $this->combineDateAndTime(
                $data['start_date'] ?? null,
                $data['start_time'] ?? null,
                '00:00:00'
            );
            unset($data['start_time']);
        }

        if (array_key_exists('end_date', $data)) {
            $fallbackEnd = ! empty($data['is_all_day']) ? '23:59:59' : '00:00:00';
            $data['end_date'] = $this->combineDateAndTime(
                $data['end_date'] ?? null,
                $data['end_time'] ?? null,
                $fallbackEnd
            );
            unset($data['end_time']);
        }

        if (array_key_exists('max_participants', $data) && $data['max_participants'] === '') {
            $data['max_participants'] = null;
        }

        $data['price'] = $data['price'] ?? 0;

        return $data;
    }

    private function combineDateAndTime(mixed $date, mixed $time, string $fallbackTime): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $dateString = Carbon::parse($date)->toDateString();

        if ($time === null || $time === '') {
            return $dateString.' '.$fallbackTime;
        }

        $timeString = Carbon::parse($time)->format('H:i:s');

        return $dateString.' '.$timeString;
    }
}
