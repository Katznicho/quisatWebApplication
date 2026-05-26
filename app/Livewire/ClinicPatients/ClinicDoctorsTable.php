<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicDoctor;
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

class ClinicDoctorsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicDoctor::query()
                    ->where('business_id', auth()->user()->business_id ?? 0)
                    ->latest()
            )
            ->heading('Doctors')
            ->description('Maintain the clinic doctor directory used when booking appointments and consultations.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('specialization')
                    ->placeholder('General practice')
                    ->searchable(),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->headerActions([
                Action::make('add_doctor')
                    ->label('Add doctor')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form($this->doctorFormSchema())
                    ->action(function (array $data): void {
                        ClinicDoctor::create(array_merge($data, [
                            'business_id' => auth()->user()->business_id,
                        ]));
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->doctorFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No doctors configured yet')
            ->emptyStateDescription('Add clinic doctors so appointment and consultation forms can use dropdown selection.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function doctorFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->placeholder('e.g. Dr. Sarah Nakanjako')
                ->required()
                ->maxLength(255),
            TextInput::make('specialization')
                ->placeholder('e.g. Pediatrics'),
            TextInput::make('phone')
                ->placeholder('+256 700 000 000')
                ->maxLength(50),
            TextInput::make('email')
                ->placeholder('doctor@clinic.com')
                ->email()
                ->maxLength(255),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->placeholder('Select status')
                ->required(),
            Textarea::make('notes')
                ->placeholder('Add availability notes, room assignment, or other internal details')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-doctors-table');
    }
}
