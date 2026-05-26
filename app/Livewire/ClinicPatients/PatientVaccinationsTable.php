<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicPatient;
use App\Models\ClinicPatientVaccination;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class PatientVaccinationsTable extends Component implements HasForms, HasTable
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
                ClinicPatientVaccination::query()
                    ->where('clinic_patient_id', $this->patient->id)
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('administered_date')
            )
            ->heading('Vaccinations')
            ->description('Track immunization schedules, administered doses, next due dates, and batch details.')
            ->columns([
                TextColumn::make('vaccine_name')
                    ->label('Vaccine')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dose_label')
                    ->label('Dose')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'administered',
                        'danger' => 'missed',
                        'warning' => 'deferred',
                    ]),
                TextColumn::make('scheduled_date')
                    ->date('d M Y')
                    ->placeholder('—'),
                TextColumn::make('administered_date')
                    ->label('Given')
                    ->date('d M Y')
                    ->placeholder('—'),
                TextColumn::make('next_due_date')
                    ->label('Next due')
                    ->date('d M Y')
                    ->placeholder('—'),
                TextColumn::make('batch_number')
                    ->label('Batch')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('add_vaccination')
                    ->label('Add vaccination')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->form($this->vaccinationFormSchema())
                    ->action(function (array $data): void {
                        ClinicPatientVaccination::create([
                            'business_id' => $this->patient->business_id,
                            'clinic_patient_id' => $this->patient->id,
                            'recorded_by' => auth()->id(),
                            'vaccine_name' => $data['vaccine_name'],
                            'dose_label' => $data['dose_label'] ?? null,
                            'scheduled_date' => $data['scheduled_date'] ?? null,
                            'administered_date' => $data['administered_date'] ?? null,
                            'next_due_date' => $data['next_due_date'] ?? null,
                            'status' => $data['status'],
                            'batch_number' => $data['batch_number'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->vaccinationFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No vaccination records yet')
            ->emptyStateDescription('Add the first immunization entry for this patient.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    protected function vaccinationFormSchema(): array
    {
        return [
            TextInput::make('vaccine_name')
                ->placeholder('e.g. Measles-Rubella')
                ->required()
                ->maxLength(255),
            TextInput::make('dose_label')
                ->maxLength(80)
                ->placeholder('e.g. Dose 1, Booster'),
            Select::make('status')
                ->options([
                    'scheduled' => 'Scheduled',
                    'administered' => 'Administered',
                    'missed' => 'Missed',
                    'deferred' => 'Deferred',
                ])
                ->placeholder('Select vaccination status')
                ->default('scheduled')
                ->required(),
            DatePicker::make('scheduled_date')
                ->placeholder('Select scheduled date'),
            DatePicker::make('administered_date')
                ->placeholder('Select administered date'),
            DatePicker::make('next_due_date')
                ->placeholder('Select next due date'),
            TextInput::make('batch_number')
                ->placeholder('e.g. BATCH-2026-014')
                ->maxLength(255),
            Textarea::make('notes')
                ->placeholder('Add reaction notes, stock notes, or reminders for the next dose')
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
        return view('livewire.clinic-patients.patient-vaccinations-table');
    }
}
