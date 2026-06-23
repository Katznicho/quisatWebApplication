<?php

namespace App\Models;

use App\Support\ProductCategory;
use App\Support\StationeryHub;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_id',
        'hub',
        'name',
        'sku',
        'description',
        'key_features',
        'whats_in_box',
        'price',
        'category',
        'grade',
        'delivery_days',
        'quality_grade',
        'image_path',
        'sizes',
        'stock_quantity',
        'low_stock_threshold',
        'is_available',
        'status',
        'rating',
        'total_ratings',
        'is_on_sale',
        'sale_price',
        'promotion_label',
        'promotion_starts_at',
        'promotion_ends_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sizes' => 'array',
        'is_available' => 'boolean',
        'is_on_sale' => 'boolean',
        'sale_price' => 'decimal:2',
        'promotion_starts_at' => 'datetime',
        'promotion_ends_at' => 'datetime',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'delivery_days' => 'integer',
        'rating' => 'decimal:2',
        'total_ratings' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (Product $product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }

            if (empty($product->sku) && $product->business_id) {
                $product->sku = static::generateUniqueSku(
                    (int) $product->business_id,
                    $product->hub ?? StationeryHub::KIDZ_MART
                );
            }
        });
    }

    public static function generateUniqueSku(int $businessId, string $hub = StationeryHub::KIDZ_MART): string
    {
        $prefix = $hub === StationeryHub::HUB ? 'SH' : 'KM';

        do {
            $sku = sprintf('%s-%d-%s', $prefix, $businessId, strtoupper(Str::random(6)));
        } while (static::where('business_id', $businessId)->where('sku', $sku)->exists());

        return $sku;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function isStationery(): bool
    {
        return ($this->hub ?? StationeryHub::KIDZ_MART) === StationeryHub::HUB;
    }

    public function isLowStock(): bool
    {
        $threshold = (int) ($this->low_stock_threshold ?? 15);

        return (int) $this->stock_quantity <= $threshold;
    }

    public function isPromotionActive(): bool
    {
        if (! $this->is_on_sale || $this->sale_price === null) {
            return false;
        }

        if ((float) $this->sale_price >= (float) ($this->price ?? 0)) {
            return false;
        }

        $now = now();

        if ($this->promotion_starts_at && $now->lt($this->promotion_starts_at)) {
            return false;
        }

        if ($this->promotion_ends_at && $now->gt($this->promotion_ends_at)) {
            return false;
        }

        return true;
    }

    public function effectivePrice(): float
    {
        return $this->isPromotionActive()
            ? (float) $this->sale_price
            : (float) ($this->price ?? 0);
    }

    public function discountPercent(): ?int
    {
        if (! $this->isPromotionActive()) {
            return null;
        }

        $price = (float) $this->price;
        if ($price <= 0) {
            return null;
        }

        return (int) round((($price - (float) $this->sale_price) / $price) * 100);
    }
    public function setCategoryAttribute(?string $value): void
    {
        if ($this->isStationery() || ($this->attributes['hub'] ?? null) === StationeryHub::HUB) {
            $this->attributes['category'] = $value ? trim($value) : null;

            return;
        }

        $this->attributes['category'] = ProductCategory::normalize($value);
    }

    public function getCategoryAttribute(?string $value): ?string
    {
        if ($this->isStationery() || ($this->attributes['hub'] ?? null) === StationeryHub::HUB) {
            return $value;
        }

        return ProductCategory::normalize($value);
    }
}
