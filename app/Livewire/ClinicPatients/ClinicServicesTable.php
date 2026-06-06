<?php

namespace App\Livewire\ClinicPatients;

use App\Livewire\ClinicPatients\Concerns\DisablesBrowserAutocomplete;
use App\Models\ClinicService;
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

class ClinicServicesTable extends Component implements HasForms, HasTable
{
    use DisablesBrowserAutocomplete;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicService::query()
                    ->where('business_id', auth()->user()->business_id ?? 0)
                    ->latest()
            )
            ->heading('Clinic services')
            ->description('Parent-facing services in the app. Parents can also book using Appointment Types — add at least one of these plus doctors.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->placeholder('—'),
                TextColumn::make('price')
                    ->money('UGX')
                    ->placeholder('—'),
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
                Action::make('add_service')
                    ->label('Add service')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form($this->serviceFormSchema())
                    ->action(function (array $data): void {
                        ClinicService::create(array_merge($data, [
                            'business_id' => auth()->user()->business_id,
                        ]));
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->serviceFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No clinic services yet')
            ->emptyStateDescription('Add services parents can see when browsing your clinic in the Quisat app.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function serviceFormSchema(): array
    {
        return [
            $this->clinicTextInput('name')
                ->placeholder('e.g. Pediatric check-up')
                ->required()
                ->maxLength(255),
            $this->clinicTextInput('duration_minutes')
                ->label('Duration (minutes)')
                ->numeric()
                ->minValue(1)
                ->maxValue(480)
                ->placeholder('e.g. 30'),
            $this->clinicTextInput('price')
                ->label('Price (UGX)')
                ->numeric()
                ->minValue(0)
                ->placeholder('Optional'),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->required(),
            $this->clinicTextarea('description')
                ->placeholder('Short description for parents'),
        ];
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-services-table');
    }
}
