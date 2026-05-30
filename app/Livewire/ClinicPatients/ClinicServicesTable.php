<?php

namespace App\Livewire\ClinicPatients;

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
            ->description('Services shown to parents in the app (e.g. check-up, vaccination, review). Separate from appointment types used in staff booking forms.')
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
            TextInput::make('name')
                ->placeholder('e.g. Pediatric check-up')
                ->required()
                ->maxLength(255),
            TextInput::make('duration_minutes')
                ->label('Duration (minutes)')
                ->numeric()
                ->minValue(1)
                ->maxValue(480)
                ->placeholder('e.g. 30'),
            TextInput::make('price')
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
            Textarea::make('description')
                ->placeholder('Short description for parents')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.clinic-services-table');
    }
}
