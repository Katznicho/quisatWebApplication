<?php

namespace App\Livewire\ClinicPatients;

use App\Livewire\ClinicPatients\Concerns\DisablesBrowserAutocomplete;
use App\Models\ClinicAppointmentType;
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

class ClinicAppointmentTypesTable extends Component implements HasForms, HasTable
{
    use DisablesBrowserAutocomplete;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicAppointmentType::query()
                    ->where('business_id', auth()->user()->business_id ?? 0)
                    ->latest()
            )
            ->heading('Appointment types')
            ->description('Visit types for staff forms and parent booking in the app (e.g. Consultation, Follow-up).')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('applies_to')
                    ->label('Applies to')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('add_type')
                    ->label('Add type')
                    ->icon('heroicon-o-tag')
                    ->color('primary')
                    ->form($this->typeFormSchema())
                    ->action(function (array $data): void {
                        ClinicAppointmentType::create(array_merge($data, [
                            'business_id' => auth()->user()->business_id,
                        ]));
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->typeFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No appointment types yet')
            ->emptyStateDescription('Add appointment and consultation types to power dropdown selection in patient workflows.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function typeFormSchema(): array
    {
        return [
            $this->clinicTextInput('name')
                ->placeholder('e.g. Consultation')
                ->required()
                ->maxLength(255),
            Select::make('applies_to')
                ->options([
                    'appointments' => 'Appointments',
                    'visits' => 'Consultations / Visits',
                    'both' => 'Both',
                ])
                ->placeholder('Select where this type should appear')
                ->default('both')
                ->required(),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->placeholder('Select status')
                ->default('active')
                ->required(),
            $this->clinicTextarea('description')
                ->placeholder('Describe when staff should use this type'),
        ];
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-appointment-types-table');
    }
}
