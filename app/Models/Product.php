<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'description',
        'price',
        'category',
        'image_path',
        'sizes',
        'stock_quantity',
        'is_available',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sizes' => 'array',
        'is_available' => 'boolean',
        'stock_quantity' => 'integer',
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
}
