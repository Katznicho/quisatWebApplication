<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicPatient;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ClinicDashboardPatientsTable extends Component implements HasForms, HasTable
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
                ClinicPatient::query()
                    ->where('business_id', auth()->user()->business_id ?? 0)
                    ->with(['parentGuardian', 'student'])
                    ->latest()
            )
            ->heading('Patients')
            ->description('Manage clinic children, linked school records, and patient access to care.')
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular()
                    ->getStateUsing(function (ClinicPatient $record): ?string {
                        if (empty($record->photo)) {
                            return null;
                        }

                        return asset('storage/'.ltrim($record->photo, '/'));
                    })
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Patient&background=2563eb&color=ffffff&size=128'),
                TextColumn::make('full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('first_name', $direction)->orderBy('last_name', $direction)),
                TextColumn::make('patient_number')
                    ->label('Patient no.')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('school_access_code')
                    ->label('Access code')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('parentGuardian.full_name')
                    ->label('Guardian')
                    ->placeholder('—')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('parentGuardian', function ($guardianQuery) use ($search) {
                            $guardianQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->headerActions([
                Action::make('add_patient')
                    ->label('Add patient')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->url(route('clinic-patients.create')),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (ClinicPatient $record): string => route('clinic-patients.show', $record)),
                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (ClinicPatient $record): string => route('clinic-patients.edit', $record)),
                DeleteAction::make()
                    ->action(function (ClinicPatient $record): void {
                        if ($record->photo && Storage::disk('public')->exists($record->photo)) {
                            Storage::disk('public')->delete($record->photo);
                        }

                        $record->delete();
                    }),
            ])
            ->emptyStateHeading('No clinic patients yet')
            ->emptyStateDescription('Import or register the first child to start the clinic workflow.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-dashboard-patients-table');
    }
}
