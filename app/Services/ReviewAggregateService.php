<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessReview;
use App\Models\Product;
use App\Models\ProductReview;

class ReviewAggregateService
{
    public function refreshProductRating(int $productId): void
    {
        $stats = ProductReview::query()
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        Product::whereKey($productId)->update([
            'rating' => $stats && $stats->total > 0 ? round((float) $stats->avg_rating, 2) : null,
            'total_ratings' => (int) ($stats->total ?? 0),
        ]);
    }

    public function refreshBusinessRating(int $businessId): void
    {
        $stats = BusinessReview::query()
            ->where('business_id', $businessId)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        Business::whereKey($businessId)->update([
            'rating' => $stats && $stats->total > 0 ? round((float) $stats->avg_rating, 2) : null,
            'total_ratings' => (int) ($stats->total ?? 0),
        ]);
    }
}
