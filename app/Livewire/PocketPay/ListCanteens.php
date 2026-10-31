<?php

namespace App\Livewire\PocketPay;

use App\Exceptions\PocketPayException;
use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenFloatTopup;
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
use Illuminate\Support\Facades\Storage;
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
                            ->placeholder('Choose a school')
                            ->options(fn (): array => $this->schoolOptions())
                            ->searchable()
                            ->required()
                            ->visible(TenantScope::isSuperAdmin()),
                        ...static::canteenFields(),
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
                    ->visible(fn (Canteen $record): bool => $record->isActive() && PocketPayAccess::canManageFloat())
                    ->modalHeading(fn (Canteen $record): string => 'Top up '.$record->name)
                    ->modalDescription('Record cash paid at the canteen. The float is credited immediately and the proof is kept with the statement.')
                    ->form(static::floatTopupFields())
                    ->action(fn (Canteen $record, array $data) => static::recordFloatTopup($record, $data)),
                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->fillForm(fn (Canteen $record): array => $record->only(['name', 'location', 'low_float_threshold', 'status']))
                    ->form([
                        ...static::canteenFields(),
                        Forms\Components\Select::make('status')
                            ->placeholder('Choose a status')
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

    public static function canteenFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required()->maxLength(120)->placeholder('e.g. Main Canteen'),
            Forms\Components\TextInput::make('location')->maxLength(120)->placeholder('e.g. Block A, ground floor'),
            Forms\Components\TextInput::make('low_float_threshold')
                ->label('Low float warning (UGX)')
                ->placeholder('e.g. 50000')
                ->numeric()
                ->minValue(0)
                ->default(0),
        ];
    }

    /**
     * Form for a cash float top-up paid at the canteen, backed by a proof of payment.
     */
    public static function floatTopupFields(): array
    {
        return [
            Forms\Components\TextInput::make('amount')
                ->label('Amount received (UGX)')
                ->placeholder('e.g. 500000')
                ->numeric()
                ->required()
                ->minValue(PocketPayService::MIN_TOPUP)
                ->maxValue(PocketPayService::MAX_FLOAT_TOPUP),
            Forms\Components\DatePicker::make('paid_on')
                ->label('Date paid')
                ->placeholder('Choose the payment date')
                ->required()
                ->default(now())
                ->maxDate(now()),
            Forms\Components\TextInput::make('payment_reference')
                ->label('Receipt / reference number')
                ->placeholder('e.g. RCPT-00231')
                ->maxLength(100),
            Forms\Components\FileUpload::make('proof')
                ->label('Proof of payment')
                ->required()
                ->disk(CanteenFloatTopup::PROOF_DISK)
                ->directory(CanteenFloatTopup::PROOF_DIRECTORY)
                ->visibility('private')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                ->maxSize(5120)
                ->storeFileNamesIn('proof_name')
                ->helperText('Photo or scan of the receipt: JPG, PNG or PDF, up to 5 MB.'),
            Forms\Components\Textarea::make('note')
                ->label('Note (optional)')
                ->placeholder('e.g. Cash handed over by the bursar')
                ->maxLength(255),
        ];
    }

    public static function recordFloatTopup(Canteen $canteen, array $data): void
    {
        abort_unless(PocketPayAccess::canManageFloat(), 403);

        try {
            $topup = app(PocketPayService::class)->recordFloatTopup(
                $canteen,
                (float) $data['amount'],
                $data['proof'],
                Auth::user(),
                $data['proof_name'] ?? null,
                $data['paid_on'] ?? null,
                $data['payment_reference'] ?? null,
                $data['note'] ?? null,
            );

            Notification::make()
                ->title('Float topped up')
                ->body('UGX '.number_format($topup->amount).' added to '.$canteen->name.'.')
                ->success()
                ->send();
        } catch (PocketPayException $e) {
            Storage::disk(CanteenFloatTopup::PROOF_DISK)->delete($data['proof']);
            Notification::make()->title($e->getMessage())->danger()->send();
        }
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
