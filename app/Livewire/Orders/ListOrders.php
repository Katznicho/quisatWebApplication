<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Services\BusinessWalletService;
use App\Support\StationeryHub;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListOrders extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $hub = StationeryHub::KIDZ_MART;

    public function mount(string $hub = StationeryHub::KIDZ_MART): void
    {
        $this->hub = $hub;
    }

    public function table(Table $table): Table
    {
        $isSuperAdmin = (int) Auth::user()->business_id === 1;
        $isStationery = $this->hub === StationeryHub::HUB;

        $columns = [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('M d, Y H:i')
                ->sortable(),
        ];

        if ($isSuperAdmin) {
            $columns[] = Tables\Columns\TextColumn::make('business.name')
                ->label('Business')
                ->searchable()
                ->sortable();
        }

        $columns = array_merge($columns, [
            Tables\Columns\TextColumn::make('order_number')
                ->label('Order #')
                ->searchable()
                ->copyable(),
            Tables\Columns\TextColumn::make('customer_name')
                ->label('Customer')
                ->searchable()
                ->description(fn (Order $record): ?string => $record->customer_phone),
            Tables\Columns\TextColumn::make('items_count')
                ->label('Items')
                ->counts('items')
                ->badge(),
            Tables\Columns\TextColumn::make('total_amount')
                ->label('Total (UGX)')
                ->numeric()
                ->sortable()
                ->formatStateUsing(fn ($state, Order $record) => number_format((float) ($state ?? $record->total ?? 0))),
            Tables\Columns\TextColumn::make('payment_method')
                ->label('Payment')
                ->formatStateUsing(fn (?string $state): string => $this->formatPaymentMethod($state))
                ->badge(),
            Tables\Columns\TextColumn::make('payment_status')
                ->label('Paid')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'paid' => 'success',
                    'failed' => 'danger',
                    default => 'warning',
                }),
            Tables\Columns\TextColumn::make('customer_received_at')
                ->label('Customer received')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => $state ? 'Confirmed' : 'Pending')
                ->description(fn (Order $record): ?string => $record->customer_received_at?->format('M d, Y H:i'))
                ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
            Tables\Columns\TextColumn::make('funds_released_at')
                ->label('Funds')
                ->badge()
                ->formatStateUsing(function (?string $state, Order $record): string {
                    if ($record->payment_status !== 'paid' || ! in_array($record->payment_method, ['mtn_mobile_money', 'airtel_money', 'card'], true)) {
                        return 'N/A';
                    }

                    return $state ? 'Released' : 'Held';
                })
                ->color(function (?string $state, Order $record): string {
                    if ($record->payment_status !== 'paid' || ! in_array($record->payment_method, ['mtn_mobile_money', 'airtel_money', 'card'], true)) {
                        return 'gray';
                    }

                    return $state ? 'success' : 'warning';
                }),
            Tables\Columns\TextColumn::make('status')
                ->label('Order status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                    'shipped', 'processing', 'confirmed' => 'info',
                    default => 'warning',
                })
                ->sortable(),
        ]);

        if ($isStationery) {
            $columns[] = Tables\Columns\TextColumn::make('fulfillment_status')
                ->label('Fulfillment')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => StationeryHub::fulfillmentStatuses()[$state ?? 'new'] ?? ucfirst((string) $state))
                ->color(fn (?string $state): string => match ($state) {
                    'delivered' => 'success',
                    'dispatched' => 'info',
                    'packed' => 'warning',
                    default => 'gray',
                });
        }

        $filters = [
            Tables\Filters\SelectFilter::make('status')
                ->options($this->statusOptions()),
            Tables\Filters\SelectFilter::make('payment_status')
                ->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                ]),
        ];

        if ($isSuperAdmin) {
            $filters[] = Tables\Filters\SelectFilter::make('business_id')
                ->label('Business')
                ->relationship('business', 'name');
        }

        if ($isStationery) {
            $filters[] = Tables\Filters\SelectFilter::make('fulfillment_status')
                ->label('Fulfillment')
                ->options(StationeryHub::fulfillmentStatuses());
        }

        $actions = [
            ViewAction::make()
                ->modalHeading(fn (Order $record): string => 'Order '.$record->order_number)
                ->modalContent(fn (Order $record): View => view('orders.partials.show', [
                    'order' => $record->loadMissing(['items.product', 'business', 'customerReceivedBy']),
                ])),
            Action::make('release_funds')
                ->label('Confirm received')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (Order $record): bool => $record->fundsAreHeld())
                ->requiresConfirmation()
                ->modalHeading('Confirm order received')
                ->modalDescription('This marks the order as delivered and releases the held payment into the business available balance for withdrawal.')
                ->action(function (Order $record): void {
                    $this->confirmOrderReceived($record);
                }),
            Action::make('update_status')
                ->label('Update status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Select::make('status')
                        ->label('Order status')
                        ->options($this->statusOptions())
                        ->required()
                        ->default(fn (Order $record): string => $record->status),
                ])
                ->action(function (Order $record, array $data): void {
                    $record->update(['status' => $data['status']]);

                    if ($data['status'] === 'delivered' && $record->fresh()->fundsAreHeld()) {
                        $this->confirmOrderReceived($record->fresh());
                    } else {
                        Notification::make()
                            ->title('Order status updated')
                            ->success()
                            ->send();
                    }
                }),
        ];

        if ($isStationery) {
            $actions[] = Action::make('update_fulfillment')
                ->label('Fulfillment')
                ->icon('heroicon-o-truck')
                ->form([
                    Select::make('fulfillment_status')
                        ->label('Fulfillment status')
                        ->options(StationeryHub::fulfillmentStatuses())
                        ->required()
                        ->default(fn (Order $record): string => $record->fulfillment_status ?? 'new'),
                ])
                ->action(function (Order $record, array $data): void {
                    if (! $this->canManageOrder($record)) {
                        Notification::make()->title('Unauthorized')->danger()->send();

                        return;
                    }

                    $record->update(['fulfillment_status' => $data['fulfillment_status']]);

                    if ($data['fulfillment_status'] === 'delivered' && $record->fresh()->fundsAreHeld()) {
                        $this->confirmOrderReceived($record->fresh());
                    } else {
                        Notification::make()
                            ->title('Fulfillment status updated')
                            ->success()
                            ->send();
                    }
                });
        }

        $hubLabel = $isStationery ? 'Stationery Hub' : 'Kids Mart';

        return $table
            ->query($this->ordersQuery())
            ->columns($columns)
            ->filters($filters)
            ->actions($actions)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription($isSuperAdmin
                ? "All {$hubLabel} orders from every business will appear here."
                : "Customer orders from {$hubLabel} will appear here.");
    }

    protected function confirmOrderReceived(Order $record): void
    {
        if (! $this->canManageOrder($record)) {
            Notification::make()
                ->title('Unauthorized')
                ->danger()
                ->send();

            return;
        }

        $released = app(BusinessWalletService::class)->releaseOrderFunds($record, Auth::id());

        if ($released) {
            $record->update([
                'status' => 'delivered',
                'fulfillment_status' => $record->hub === StationeryHub::HUB ? 'delivered' : $record->fulfillment_status,
            ]);

            Notification::make()
                ->title('Order confirmed — funds released to available balance')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No held funds to release for this order')
                ->warning()
                ->send();
        }
    }

    protected function canManageOrder(Order $record): bool
    {
        $businessId = (int) Auth::user()->business_id;

        return $businessId === 1 || $businessId === (int) $record->business_id;
    }

    protected function ordersQuery(): Builder
    {
        $query = Order::query()->with(['business'])->withCount('items');

        $businessId = (int) Auth::user()->business_id;

        if ($businessId !== 1) {
            $query->where('business_id', $businessId);
        }

        return $query->where('hub', $this->hub);
    }

    protected function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }

    protected function formatPaymentMethod(?string $method): string
    {
        return match ($method) {
            'mtn_mobile_money' => 'MTN Mobile Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            default => ucfirst(str_replace('_', ' ', (string) $method)),
        };
    }

    public function render(): View
    {
        return view('livewire.orders.list-orders');
    }
}
