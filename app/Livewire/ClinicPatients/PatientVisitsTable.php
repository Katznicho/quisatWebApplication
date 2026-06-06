<?php

namespace App\Livewire\ClinicPatients;

use App\Livewire\ClinicPatients\Concerns\DisablesBrowserAutocomplete;
use App\Models\ClinicAppointmentType;
use App\Models\ClinicDoctor;
use App\Models\ClinicPatient;
use App\Models\ClinicPatientVisit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PatientVisitsTable extends Component implements HasForms, HasTable
{
    use DisablesBrowserAutocomplete;
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
                ClinicPatientVisit::query()
                    ->where('clinic_patient_id', $this->patient->id)
                    ->orderByDesc('visited_at')
            )
            ->heading('Visits / EMR')
            ->description('Consultation notes, treatment plans, prescriptions, and follow-up outcomes.')
            ->columns([
                TextColumn::make('visited_at')
                    ->label('Visit date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('visit_type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                TextColumn::make('doctor_name')
                    ->label('Doctor')
                    ->placeholder('Not assigned')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'follow_up_needed',
                        'gray' => 'cancelled',
                        'primary' => 'in_progress',
                    ]),
                TextColumn::make('chief_complaint')
                    ->label('Chief complaint')
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date('d M Y')
                    ->placeholder('None'),
            ])
            ->headerActions([
                Action::make('add_visit')
                    ->label('Add visit')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->form($this->visitFormSchema())
                    ->action(function (array $data): void {
                        ClinicPatientVisit::create([
                            'business_id' => $this->patient->business_id,
                            'clinic_patient_id' => $this->patient->id,
                            'created_by' => auth()->id(),
                            'visited_at' => $data['visited_at'],
                            'doctor_name' => $data['doctor_name'] ?? null,
                            'visit_type' => $data['visit_type'],
                            'status' => $data['status'],
                            'chief_complaint' => $data['chief_complaint'] ?? null,
                            'consultation_notes' => $data['consultation_notes'] ?? null,
                            'treatment_plan' => $data['treatment_plan'] ?? null,
                            'prescriptions' => $data['prescriptions'] ?? null,
                            'lab_results' => $data['lab_results'] ?? null,
                            'follow_up_date' => $data['follow_up_date'] ?? null,
                        ]);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->visitFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No visits recorded yet')
            ->emptyStateDescription('Add the first clinical visit for this patient.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    protected function visitFormSchema(): array
    {
        return [
            DateTimePicker::make('visited_at')
                ->label('Visit date & time')
                ->placeholder('Select visit date and time')
                ->required()
                ->seconds(false),
            Select::make('doctor_name')
                ->label('Doctor')
                ->options($this->doctorOptions())
                ->placeholder('Select doctor from Doctors tab')
                ->searchable()
                ->preload(),
            Select::make('visit_type')
                ->options($this->appointmentTypeOptions(['visits', 'both']))
                ->placeholder('Select visit type')
                ->default(fn () => array_key_first($this->appointmentTypeOptions(['visits', 'both'])))
                ->required(),
            Select::make('status')
                ->options([
                    'completed' => 'Completed',
                    'in_progress' => 'In progress',
                    'follow_up_needed' => 'Follow-up needed',
                    'cancelled' => 'Cancelled',
                ])
                ->placeholder('Select visit status')
                ->default('completed')
                ->required(),
            $this->clinicTextarea('chief_complaint', 2)
                ->placeholder('e.g. Fever, cough, loss of appetite for 3 days'),
            $this->clinicTextarea('consultation_notes', 4)
                ->placeholder('Enter consultation findings, examination notes, and clinical observations'),
            $this->clinicTextarea('treatment_plan')
                ->placeholder('Describe the treatment plan and care instructions'),
            $this->clinicTextarea('prescriptions')
                ->placeholder('List medicines, dosage, and duration'),
            $this->clinicTextarea('lab_results')
                ->placeholder('Record requested tests or lab result summary'),
            DatePicker::make('follow_up_date')
                ->placeholder('Select follow-up date'),
        ];
    }

    protected function doctorOptions(): array
    {
        return ClinicDoctor::query()
            ->where('business_id', $this->patient->business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    protected function appointmentTypeOptions(array $appliesTo): array
    {
        $options = ClinicAppointmentType::query()
            ->where('business_id', $this->patient->business_id)
            ->where('status', 'active')
            ->whereIn('applies_to', $appliesTo)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        return ! empty($options)
            ? $options
            : [
                'consultation' => 'Consultation',
                'review' => 'Review',
                'emergency' => 'Emergency',
                'vaccination' => 'Vaccination',
                'follow_up' => 'Follow-up',
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
        return view('livewire.clinic-patients.patient-visits-table');
    }
}
