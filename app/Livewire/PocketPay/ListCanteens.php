<?php

namespace App\Livewire\PocketPay;

use App\Exceptions\PocketPayException;
use App\Models\Business;
use App\Models\Canteen;
use App\Services\PocketPayService;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListCanteens extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        PocketPayAccess::authorize();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TenantScope::apply(Canteen::query()->with('business:id,name')->withCount('attendants')))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Canteen $record): string => route('pocket-pay.canteens.show', $record))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('School')
                    ->visible(TenantScope::isSuperAdmin())
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')->placeholder('—'),
                Tables\Columns\TextColumn::make('float_balance')
                    ->label('Float (UGX)')
                    ->numeric()
                    ->sortable()
                    ->color(fn (Canteen $record): string => $record->isFloatLow() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('attendants_count')->label('Attendants'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('New canteen')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('business_id')
                            ->label('School')
                            ->options(fn (): array => $this->schoolOptions())
                            ->searchable()
                            ->required()
                            ->visible(TenantScope::isSuperAdmin()),
                        ...$this->canteenFields(),
                    ])
                    ->action(function (array $data): void {
                        $businessId = TenantScope::isSuperAdmin() ? (int) $data['business_id'] : TenantScope::businessId();

                        Canteen::create([
                            'business_id' => $businessId,
                            'name' => $data['name'],
                            'location' => $data['location'] ?? null,
                            'low_float_threshold' => (int) ($data['low_float_threshold'] ?? 0),
                            'created_by' => Auth::id(),
                        ]);

                        Notification::make()->title('Canteen created')->success()->send();
                    }),
            ])
            ->actions([
                Action::make('topup')
                    ->label('Top up float')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Canteen $record): bool => $record->isActive())
                    ->modalDescription('A mobile money prompt is sent to the number below. The float is credited once the payment completes.')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (UGX)')
                            ->numeric()
                            ->required()
                            ->minValue(PocketPayService::MIN_TOPUP)
                            ->maxValue(PocketPayService::MAX_TOPUP),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Mobile money number')
                            ->tel()
                            ->required()
                            ->default(fn () => Auth::user()?->phone),
                    ])
                    ->action(function (Canteen $record, array $data): void {
                        try {
                            app(PocketPayService::class)->initiateTopup($record, (float) $data['amount'], $data['phone_number'], Auth::user());
                            Notification::make()->title('Prompt sent')->body('Approve the mobile money prompt to credit the float.')->success()->send();
                        } catch (PocketPayException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->fillForm(fn (Canteen $record): array => $record->only(['name', 'location', 'low_float_threshold', 'status']))
                    ->form([
                        ...$this->canteenFields(),
                        Forms\Components\Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->required()
                            ->helperText('Attendants of an inactive canteen cannot sell.'),
                    ])
                    ->action(function (Canteen $record, array $data): void {
                        $record->update([
                            'name' => $data['name'],
                            'location' => $data['location'] ?? null,
                            'low_float_threshold' => (int) ($data['low_float_threshold'] ?? 0),
                            'status' => $data['status'],
                        ]);

                        Notification::make()->title('Canteen updated')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No canteens yet')
            ->emptyStateDescription('Create a canteen, top up its float, then add attendants.');
    }

    protected function canteenFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required()->maxLength(120),
            Forms\Components\TextInput::make('location')->maxLength(120),
            Forms\Components\TextInput::make('low_float_threshold')
                ->label('Low float warning (UGX)')
                ->numeric()
                ->minValue(0)
                ->default(0),
        ];
    }

    protected function schoolOptions(): array
    {
        $service = app(PocketPayService::class);

        return Business::query()
            ->where('id', '!=', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'enabled_feature_ids'])
            ->filter(fn (Business $business) => $service->isEnabledFor($business))
            ->pluck('name', 'id')
            ->all();
    }

    public function render(): View
    {
        return view('livewire.pocket-pay.table');
    }
}
