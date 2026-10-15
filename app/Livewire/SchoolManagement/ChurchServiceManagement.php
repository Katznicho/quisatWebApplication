<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Attendance;
use App\Models\ChurchService;
use App\Models\ClassRoom;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ChurchServiceManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use \App\Livewire\Concerns\DownloadsTableDocuments;

    public string $reportDate = '';

    public string $reportServiceId = '';

    public string $reportClassId = '';

    public function mount(): void
    {
        $business = auth()->user()?->business;
        abort_unless($business && $business->isChurch(), 403);
        $this->reportDate = Carbon::now($business->timezoneName())->toDateString();
    }

    public function table(Table $table): Table
    {
        $businessId = auth()->user()->business_id;

        return $table
            ->query(ChurchService::query()->where('business_id', $businessId))
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Service')->searchable(),
                Tables\Columns\TextColumn::make('schedule')
                    ->label('When')
                    ->state(fn (ChurchService $record) => $record->scheduleLabel()),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray'),
            ])
            ->headerActions([
                ...$this->tableDocumentActions('Services'),
                Tables\Actions\CreateAction::make()
                    ->label('Add Service')
                    ->form($this->formSchema())
                    ->using(function (array $data) use ($businessId): ChurchService {
                        $this->assertTimes($data);

                        return ChurchService::query()->create($this->payload($data, (int) $businessId));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->assertTimes($data);

                        return $this->payload($data, (int) auth()->user()->business_id);
                    }),
            ]);
    }

    public function render()
    {
        $businessId = (int) auth()->user()->business_id;
        $services = ChurchService::query()->where('business_id', $businessId)->orderBy('name')->get();
        $classes = ClassRoom::query()->where('business_id', $businessId)->orderBy('name')->get(['id', 'name']);

        $records = Attendance::query()
            ->where('business_id', $businessId)
            ->when($this->reportDate !== '', fn ($query) => $query->whereDate('attendance_date', $this->reportDate))
            ->when($this->reportServiceId !== '', fn ($query) => $query->where('church_service_id', (int) $this->reportServiceId))
            ->when($this->reportClassId !== '', fn ($query) => $query->where('class_room_id', (int) $this->reportClassId))
            ->get(['check_in_time', 'check_out_time']);

        $checkedIn = $records->filter(fn ($row) => filled($row->check_in_time))->count();
        $checkedOut = $records->filter(fn ($row) => filled($row->check_out_time))->count();
        $stillPresent = $records->filter(fn ($row) => filled($row->check_in_time) && blank($row->check_out_time))->count();

        return view('livewire.school-management.church-service-management', [
            'services' => $services,
            'classes' => $classes,
            'checkedIn' => $checkedIn,
            'checkedOut' => $checkedOut,
            'stillPresent' => $stillPresent,
        ]);
    }

    private function formSchema(): array
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return [
            TextInput::make('name')->label('Service name')->required()->maxLength(120),
            Select::make('schedule_type')
                ->label('Schedule')
                ->options(['weekly' => 'Weekly', 'date' => 'Specific date'])
                ->required()
                ->reactive()
                ->default('weekly'),
            Select::make('weekday')
                ->label('Day')
                ->options($days)
                ->required(fn (Get $get): bool => $get('schedule_type') !== 'date')
                ->visible(fn (Get $get): bool => $get('schedule_type') !== 'date'),
            \Filament\Forms\Components\DatePicker::make('service_date')
                ->label('Date')
                ->required(fn (Get $get): bool => $get('schedule_type') === 'date')
                ->visible(fn (Get $get): bool => $get('schedule_type') === 'date'),
            TimePicker::make('starts_at')->label('Start time')->required()->seconds(false),
            TimePicker::make('ends_at')->label('End time')->required()->seconds(false),
            Toggle::make('is_active')->label('Active')->default(true)
                ->helperText('Inactive services stay in attendance history and cannot be used for a new check-in.'),
        ];
    }

    private function payload(array $data, int $businessId): array
    {
        $weekly = ($data['schedule_type'] ?? 'weekly') !== 'date';

        return [
            'business_id' => $businessId,
            'name' => $data['name'],
            'schedule_type' => $weekly ? 'weekly' : 'date',
            'weekday' => $weekly ? (int) $data['weekday'] : null,
            'service_date' => $weekly ? null : $data['service_date'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'timezone' => auth()->user()?->business?->timezoneName() ?: 'Africa/Nairobi',
        ];
    }

    private function assertTimes(array $data): void
    {
        $start = Carbon::parse($data['starts_at'] ?? null);
        $end = Carbon::parse($data['ends_at'] ?? null);
        if ($end->lessThanOrEqualTo($start)) {
            Notification::make()->title('End time must be later than start time.')->danger()->send();
            throw ValidationException::withMessages([
                'ends_at' => 'End time must be later than start time.',
            ]);
        }
    }
}
