<?php

namespace App\Models;

use App\Support\TimeField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class KidsFunVenue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'description',
        'location',
        'open_time',
        'close_time',
        'activities',
        'prices',
        'images',
        'website_link',
        'social_media_handles',
        'booking_link',
        'status',
        'created_by',
    ];

    protected $casts = [
        'activities' => 'array',
        'prices' => 'array',
        'images' => 'array',
        'social_media_handles' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($venue) {
            if (empty($venue->uuid)) {
                $venue->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function getOpenTimeAttribute(?string $value): ?string
    {
        return TimeField::formatForInput($value);
    }

    public function setOpenTimeAttribute(?string $value): void
    {
        $this->attributes['open_time'] = TimeField::normalizeForStorage($value);
    }

    public function getCloseTimeAttribute(?string $value): ?string
    {
        return TimeField::formatForInput($value);
    }

    public function setCloseTimeAttribute(?string $value): void
    {
        $this->attributes['close_time'] = TimeField::normalizeForStorage($value);
    }
}
