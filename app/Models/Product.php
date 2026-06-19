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
        'description',
        'price',
        'category',
        'grade_levels',
        'delivery_days',
        'quality_grade',
        'image_path',
        'sizes',
        'stock_quantity',
        'low_stock_threshold',
        'is_available',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sizes' => 'array',
        'grade_levels' => 'array',
        'is_available' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'delivery_days' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (Product $product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
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
