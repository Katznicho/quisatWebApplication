<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicAppointment;
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

class ClinicDashboardAppointmentsTable extends Component implements HasForms, HasTable
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
                ClinicAppointment::query()
                    ->where('business_id', auth()->user()->business_id ?? 0)
                    ->with(['patient', 'creator'])
                    ->orderByDesc('scheduled_at')
            )
            ->heading('Appointments')
            ->description('All scheduled visits including bookings made by parents in the Quisat app.')
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Date & time')
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
                    })
                    ->url(fn (ClinicAppointment $record) => $record->patient
                        ? route('clinic-patients.show', $record->patient)
                        : null),
                TextColumn::make('doctor_name')
                    ->label('Doctor')
                    ->placeholder('Not assigned')
                    ->searchable(),
                TextColumn::make('appointment_type')
                    ->label('Visit type')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'completed',
                        'gray' => 'cancelled',
                    ]),
                TextColumn::make('booked_via')
                    ->label('Booked via')
                    ->state(fn (ClinicAppointment $record): string => $record->created_by ? 'Clinic staff' : 'Parent app')
                    ->badge()
                    ->color(fn (ClinicAppointment $record): string => $record->created_by ? 'gray' : 'success'),
                TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->actions([
                Action::make('open_patient')
                    ->label('Open patient')
                    ->icon('heroicon-o-user')
                    ->url(fn (ClinicAppointment $record) => $record->patient
                        ? route('clinic-patients.show', ['clinic_patient' => $record->patient, 'tab' => 'appointments'])
                        : null)
                    ->visible(fn (ClinicAppointment $record) => (bool) $record->patient),
            ])
            ->emptyStateHeading('No appointments yet')
            ->emptyStateDescription('Appointments booked by parents in the app or scheduled by staff will appear here.')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-dashboard-appointments-table');
    }
}
