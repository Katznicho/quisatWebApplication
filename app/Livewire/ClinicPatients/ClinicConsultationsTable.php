<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicPatientVisit;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ClinicConsultationsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicPatientVisit::query()
                    ->where('business_id', auth()->user()->business_id ?? 0)
                    ->with('patient')
                    ->latest('visited_at')
            )
            ->heading('Consultations')
            ->description('Review clinical visits, treatment plans, and follow-up decisions across the clinic.')
            ->columns([
                TextColumn::make('visited_at')
                    ->label('Visit date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('patient', function ($patientQuery) use ($search) {
                            $patientQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('patient_number', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('visit_type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                TextColumn::make('doctor_name')
                    ->label('Doctor')
                    ->placeholder('Not assigned'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'follow_up_needed',
                        'gray' => 'cancelled',
                        'primary' => 'in_progress',
                    ]),
                TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date('d M Y')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('open_patient')
                    ->label('Open patient')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(fn (ClinicPatientVisit $record): string => route('clinic-patients.show', ['clinic_patient' => $record->clinic_patient_id, 'tab' => 'visits'])),
            ])
            ->emptyStateHeading('No consultations recorded yet')
            ->emptyStateDescription('Consultations will appear here after staff add clinical visits on patient profiles.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-consultations-table');
    }
}
