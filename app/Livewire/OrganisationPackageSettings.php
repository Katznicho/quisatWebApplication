<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\PackagePlan;
use App\Support\OrganisationPackage;
use App\Support\TenantScope;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class OrganisationPackageSettings extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        abort_unless(TenantScope::isSuperAdmin(), 403);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Business::query())
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Organisation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('package')
                    ->label('Package')
                    ->formatStateUsing(function (?string $state, Business $record): string {
                        $key = $state ?: OrganisationPackage::FREE;
                        $options = OrganisationPackage::planOptions();

                        return $options[$key] ?? ucfirst($key);
                    }),
                Tables\Columns\TextColumn::make('package_services')
                    ->label('Silver services')
                    ->formatStateUsing(function ($state, Business $record): string {
                        if (($record->package ?: OrganisationPackage::FREE) !== OrganisationPackage::SILVER) {
                            return '—';
                        }

                        $labels = OrganisationPackage::toggleableOptions();
                        $chosen = collect(is_array($state) ? $state : [])
                            ->map(fn ($key) => $labels[$key] ?? null)
                            ->filter()
                            ->values();

                        return $chosen->isEmpty() ? 'Clinics only' : $chosen->implode(', ');
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('package_plans')
                    ->label('Package prices')
                    ->fillForm(function (): array {
                        $plans = PackagePlan::query()->get()->keyBy('key');

                        return [
                            'gold_name' => $plans['gold']->name ?? 'Gold',
                            'gold_price' => $plans['gold']->price ?? null,
                            'silver_name' => $plans['silver']->name ?? 'Silver',
                            'silver_price' => $plans['silver']->price ?? null,
                            'free_name' => $plans['free']->name ?? 'Free',
                            'free_price' => $plans['free']->price ?? 0,
                        ];
                    })
                    ->form([
                        TextInput::make('gold_name')->label('Gold name')->required(),
                        TextInput::make('gold_price')->label('Gold price')->numeric()->placeholder('To be provided'),
                        TextInput::make('silver_name')->label('Silver name')->required(),
                        TextInput::make('silver_price')->label('Silver price')->numeric()->placeholder('To be provided'),
                        TextInput::make('free_name')->label('Free name')->required(),
                        TextInput::make('free_price')->label('Free price')->numeric()->default(0),
                    ])
                    ->action(function (array $data): void {
                        foreach (['gold', 'silver', 'free'] as $key) {
                            PackagePlan::query()->updateOrCreate(
                                ['key' => $key],
                                [
                                    'name' => $data[$key.'_name'],
                                    'price' => $data[$key.'_price'] === '' || $data[$key.'_price'] === null ? null : $data[$key.'_price'],
                                    'currency_code' => 'UGX',
                                ]
                            );
                        }

                        Notification::make()->title('Package names and prices saved')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_package')
                    ->label('Set package')
                    ->fillForm(fn (Business $record): array => [
                        'package' => $record->package ?: OrganisationPackage::FREE,
                        'package_services' => $record->package_services ?? [],
                    ])
                    ->form([
                        Select::make('package')
                            ->label('Organisation package')
                            ->options(fn () => OrganisationPackage::planOptions())
                            ->required()
                            ->reactive()
                            ->helperText('Operational school and church tools stay available on every package.'),
                        CheckboxList::make('package_services')
                            ->label('Silver community services')
                            ->options(fn () => OrganisationPackage::toggleableOptions())
                            ->columns(2)
                            ->helperText('Clinics stays available. Turn on the other community services this organisation should see.')
                            ->visible(fn (Get $get): bool => $get('package') === OrganisationPackage::SILVER),
                    ])
                    ->action(function (Business $record, array $data): void {
                        $package = $data['package'] ?: OrganisationPackage::FREE;
                        $updates = ['package' => $package];

                        if ($package === OrganisationPackage::SILVER) {
                            $allowed = array_keys(OrganisationPackage::toggleableOptions());
                            $updates['package_services'] = array_values(array_intersect(
                                is_array($data['package_services'] ?? null) ? $data['package_services'] : [],
                                $allowed
                            ));
                        }

                        $record->update($updates);

                        Notification::make()
                            ->title($record->name.' is now '.ucfirst($package))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.organisation-package-settings');
    }
}
