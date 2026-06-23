<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductCatalogService
{
    public function catalogQuery(int $businessId, string $hub): Builder
    {
        $query = Product::query()
            ->with(['business:id,name'])
            ->where('hub', $hub);

        if ($businessId !== 1) {
            $query->where('business_id', $businessId);
        }

        $paidSalesConstraint = function ($query) {
            $query->whereHas('order', function ($orderQuery) {
                $orderQuery->where('status', '!=', 'cancelled')
                    ->where('payment_status', 'paid');
            });
        };

        return $query
            ->withSum(['orderItems as units_sold' => $paidSalesConstraint], 'quantity')
            ->withSum(['orderItems as sales_revenue' => $paidSalesConstraint], 'total_price')
            ->withCount(['orderItems as sales_lines' => $paidSalesConstraint]);
    }

    /**
     * @return array{products: int, in_stock: int, units_in_stock: int, units_sold: int, sales_revenue: float}
     */
    public function summary(int $businessId, string $hub): array
    {
        $products = $this->catalogQuery($businessId, $hub)->get();

        return [
            'products' => $products->count(),
            'in_stock' => $products->where('stock_quantity', '>', 0)->count(),
            'units_in_stock' => (int) $products->sum('stock_quantity'),
            'units_sold' => (int) $products->sum('units_sold'),
            'sales_revenue' => (float) $products->sum('sales_revenue'),
        ];
    }

    public function exportCsv(Collection $products, string $hubLabel, string $currency = 'UGX', bool $includeBusiness = false): string
    {
        $headers = [
            'SKU',
            'Item name',
        ];

        if ($includeBusiness) {
            $headers[] = 'Business';
        }

        $headers = array_merge($headers, [
            'Grade',
            'Category',
            'Unit price',
            'Sale price',
            'Stock quantity',
            'Units sold',
            'Sales revenue',
            'Status',
            'Available',
        ]);

        $lines = [implode(',', $headers)];

        foreach ($products as $product) {
            $row = [
                $this->csvCell($product->sku),
                $this->csvCell($product->name),
            ];

            if ($includeBusiness) {
                $row[] = $this->csvCell($product->business?->name);
            }

            $row = array_merge($row, [
                $this->csvCell($product->grade),
                $this->csvCell($product->category),
                number_format((float) $product->price, 2, '.', ''),
                $product->isPromotionActive()
                    ? number_format((float) $product->sale_price, 2, '.', '')
                    : '',
                (int) $product->stock_quantity,
                (int) ($product->units_sold ?? 0),
                number_format((float) ($product->sales_revenue ?? 0), 2, '.', ''),
                $this->csvCell($product->status ?? 'active'),
                $product->is_available ? 'yes' : 'no',
            ]);

            $lines[] = implode(',', $row);
        }

        $totals = ['', 'TOTALS'];
        if ($includeBusiness) {
            $totals[] = '';
        }
        $totals = array_merge($totals, [
            '',
            '',
            '',
            '',
            (int) $products->sum('stock_quantity'),
            (int) $products->sum('units_sold'),
            number_format((float) $products->sum('sales_revenue'), 2, '.', ''),
            '',
            '',
        ]);

        $lines[] = implode(',', $totals);
        $lines[] = '';
        $lines[] = $this->csvCell("Report: {$hubLabel} product catalog");
        $lines[] = $this->csvCell('Generated: '.now()->format('Y-m-d H:i:s'));
        $lines[] = $this->csvCell("Currency: {$currency}");

        return implode("\n", $lines)."\n";
    }

    protected function csvCell(?string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', (string) ($value ?? ''));

        return '"'.str_replace('"', '""', $value).'"';
    }
}
