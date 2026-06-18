<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductCategory;
use Illuminate\Console\Command;

class NormalizeProductCategories extends Command
{
    protected $signature = 'products:normalize-categories';

    protected $description = 'Normalize existing Kidz Mart product categories to the standard list';

    public function handle(): int
    {
        $updated = 0;

        Product::query()
            ->whereNotNull('category')
            ->orderBy('id')
            ->chunkById(100, function ($products) use (&$updated) {
                foreach ($products as $product) {
                    $raw = $product->getRawOriginal('category');
                    $normalized = ProductCategory::normalize($raw);

                    if ($normalized !== $raw) {
                        $product->forceFill(['category' => $normalized])->saveQuietly();
                        $updated++;
                    }
                }
            });

        $this->info("Normalized {$updated} product categor".($updated === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
