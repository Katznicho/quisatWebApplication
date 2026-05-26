<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicAppointment;
use App\Models\ClinicPatient;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PatientAppointmentsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public ClinicPatient $patient;

    public function mount(ClinicPatient $patient): void
    {
        $this->patient = $patient;
        $this->authorizePatient();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicAppointment::query()
                    ->where('clinic_patient_id', $this->patient->id)
                    ->orderByDesc('scheduled_at')
            )
            ->heading('Appointments')
            ->description('Manage bookings, follow-ups, and visit scheduling for this patient.')
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Date & time')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('doctor_name')
                    ->label('Doctor')
                    ->placeholder('Not assigned')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'completed',
                        'gray' => 'cancelled',
                    ]),
                TextColumn::make('notes')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Created by')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('schedule')
                    ->label('Schedule appointment')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->form($this->appointmentFormSchema())
                    ->action(function (array $data): void {
                        ClinicAppointment::create([
                            'business_id' => $this->patient->business_id,
                            'clinic_patient_id' => $this->patient->id,
                            'scheduled_at' => $data['scheduled_at'],
                            'doctor_name' => $data['doctor_name'] ?? null,
                            'appointment_type' => $data['appointment_type'],
                            'status' => $data['status'],
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->appointmentFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No appointments yet')
            ->emptyStateDescription('Create the first appointment for this patient.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    protected function appointmentFormSchema(): array
    {
        return [
            DateTimePicker::make('scheduled_at')
                ->label('Date & time')
                ->placeholder('Select appointment date and time')
                ->required()
                ->seconds(false),
            TextInput::make('doctor_name')
                ->placeholder('e.g. Dr. Sarah Nakanjako')
                ->maxLength(255),
            Select::make('appointment_type')
                ->options([
                    'consultation' => 'Consultation',
                    'follow_up' => 'Follow-up',
                    'vaccination' => 'Vaccination',
                    'checkup' => 'Check-up',
                ])
                ->placeholder('Select appointment type')
                ->default('consultation')
                ->required(),
            Select::make('status')
                ->options([
                    'scheduled' => 'Scheduled',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->placeholder('Select status')
                ->default('scheduled')
                ->required(),
            Textarea::make('notes')
                ->placeholder('Add reminder notes, preparation instructions, or visit context')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    protected function authorizePatient(): void
    {
        $businessId = auth()->user()?->business_id;
        if (! $businessId || $this->patient->business_id !== $businessId) {
            abort(403);
        }
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.patient-appointments-table');
    }
}
