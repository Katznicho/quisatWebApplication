<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicPatient;
use App\Models\ClinicPatientGrowthRecord;
use Filament\Forms\Components\DatePicker;
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

class PatientGrowthTable extends Component implements HasForms, HasTable
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
                ClinicPatientGrowthRecord::query()
                    ->where('clinic_patient_id', $this->patient->id)
                    ->orderByDesc('recorded_on')
            )
            ->heading('Growth monitoring')
            ->description('Track height, weight, BMI, head circumference, and growth notes over time.')
            ->columns([
                TextColumn::make('recorded_on')
                    ->label('Recorded on')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('height_cm')
                    ->label('Height (cm)')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1).' cm' : '—'),
                TextColumn::make('weight_kg')
                    ->label('Weight (kg)')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1).' kg' : '—'),
                TextColumn::make('bmi')
                    ->label('BMI')
                    ->getStateUsing(fn (ClinicPatientGrowthRecord $record): string => $record->bmi ? number_format($record->bmi, 2) : '—'),
                TextColumn::make('head_circumference_cm')
                    ->label('Head circ.')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1).' cm' : '—')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('add_growth_record')
                    ->label('Record growth')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->form($this->growthFormSchema())
                    ->action(function (array $data): void {
                        ClinicPatientGrowthRecord::create([
                            'business_id' => $this->patient->business_id,
                            'clinic_patient_id' => $this->patient->id,
                            'recorded_by' => auth()->id(),
                            'recorded_on' => $data['recorded_on'],
                            'height_cm' => $data['height_cm'] ?? null,
                            'weight_kg' => $data['weight_kg'] ?? null,
                            'head_circumference_cm' => $data['head_circumference_cm'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->growthFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No growth records yet')
            ->emptyStateDescription('Add the first growth measurement for this patient.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    protected function growthFormSchema(): array
    {
        return [
            DatePicker::make('recorded_on')
                ->placeholder('Select measurement date')
                ->required()
                ->default(now()),
            TextInput::make('height_cm')
                ->label('Height (cm)')
                ->placeholder('e.g. 92.5')
                ->numeric()
                ->step(0.01),
            TextInput::make('weight_kg')
                ->label('Weight (kg)')
                ->placeholder('e.g. 13.4')
                ->numeric()
                ->step(0.01),
            TextInput::make('head_circumference_cm')
                ->label('Head circumference (cm)')
                ->placeholder('e.g. 48.2')
                ->numeric()
                ->step(0.01),
            Textarea::make('notes')
                ->placeholder('Add growth observations, nutrition notes, or developmental concerns')
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
        return view('livewire.clinic-patients.patient-growth-table');
    }
}
