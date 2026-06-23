<?php

namespace App\Livewire\Catalog;

use App\Models\Product;
use App\Support\StationeryHub;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListProductCatalog extends Component implements HasForms, HasTable
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
        $currency = Auth::user()->business?->currency_code ?? 'UGX';

        $columns = [];

        if ($isSuperAdmin) {
            $columns[] = Tables\Columns\TextColumn::make('business.name')
                ->label('Business')
                ->searchable()
                ->sortable();
        }

        $columns = array_merge($columns, [
            Tables\Columns\TextColumn::make('sku')
                ->label('SKU')
                ->searchable()
                ->copyable()
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('name')
                ->label('Item')
                ->searchable()
                ->sortable()
                ->wrap(),
            Tables\Columns\TextColumn::make('grade')
                ->label('Grade')
                ->placeholder('—')
                ->toggleable(),
            Tables\Columns\TextColumn::make('category')
                ->label('Category')
                ->placeholder('—')
                ->toggleable(),
            Tables\Columns\TextColumn::make('price')
                ->label('Price')
                ->sortable()
                ->formatStateUsing(function ($state, Product $record) use ($currency): string {
                    if ($record->isPromotionActive()) {
                        return $currency.' '.number_format((float) $record->sale_price)
                            .' (was '.number_format((float) $state).')';
                    }

                    return $currency.' '.number_format((float) $state);
                }),
            Tables\Columns\TextColumn::make('stock_quantity')
                ->label('Stock')
                ->sortable()
                ->badge()
                ->color(fn (Product $record): string => $record->isLowStock() ? 'warning' : 'success'),
            Tables\Columns\TextColumn::make('units_sold')
                ->label('Units sold')
                ->sortable()
                ->numeric()
                ->default(0),
            Tables\Columns\TextColumn::make('sales_revenue')
                ->label('Sales')
                ->sortable()
                ->formatStateUsing(fn ($state) => $currency.' '.number_format((float) ($state ?? 0))),
            Tables\Columns\IconColumn::make('is_available')
                ->label('Listed')
                ->boolean(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (?string $state): string => ($state ?? 'active') === 'active' ? 'success' : 'gray'),
        ]);

        return $table
            ->query($this->catalogQuery())
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn (): array => $this->categoryOptions()),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Listed for sale'),
                Tables\Filters\Filter::make('in_stock')
                    ->label('In stock only')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '>', 0)),
                Tables\Filters\Filter::make('has_sales')
                    ->label('Has sales')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('orderItems', function ($itemQuery) {
                            $itemQuery->whereHas('order', function ($orderQuery) {
                                $orderQuery->where('status', '!=', 'cancelled')
                                    ->where('payment_status', 'paid');
                            });
                        });
                    }),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low stock')
                    ->query(function (Builder $query): Builder {
                        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                    }),
            ])
            ->defaultSort('units_sold', 'desc')
            ->emptyStateHeading('No products in catalog')
            ->emptyStateDescription('Add products or import a CSV to build your catalog.');
    }

    protected function catalogQuery(): Builder
    {
        $businessId = (int) Auth::user()->business_id;

        $paidSalesConstraint = function ($query) {
            $query->whereHas('order', function ($orderQuery) {
                $orderQuery->where('status', '!=', 'cancelled')
                    ->where('payment_status', 'paid');
            });
        };

        $query = Product::query()
            ->with(['business'])
            ->where('hub', $this->hub)
            ->withSum(['orderItems as units_sold' => $paidSalesConstraint], 'quantity')
            ->withSum(['orderItems as sales_revenue' => $paidSalesConstraint], 'total_price');

        if ($businessId !== 1) {
            $query->where('business_id', $businessId);
        }

        return $query;
    }

    protected function categoryOptions(): array
    {
        $businessId = (int) Auth::user()->business_id;

        $query = Product::query()
            ->where('hub', $this->hub)
            ->whereNotNull('category')
            ->where('category', '!=', '');

        if ($businessId !== 1) {
            $query->where('business_id', $businessId);
        }

        return $query->distinct()
            ->orderBy('category')
            ->pluck('category', 'category')
            ->all();
    }

    public function render(): View
    {
        return view('livewire.catalog.list-product-catalog');
    }
}
