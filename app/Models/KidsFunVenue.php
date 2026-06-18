<?php

namespace App\Models;

use Carbon\Carbon;
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
        return self::formatTimeForInput($value);
    }

    public function setOpenTimeAttribute(?string $value): void
    {
        $this->attributes['open_time'] = self::normalizeTimeForStorage($value);
    }

    public function getCloseTimeAttribute(?string $value): ?string
    {
        return self::formatTimeForInput($value);
    }

    public function setCloseTimeAttribute(?string $value): void
    {
        $this->attributes['close_time'] = self::normalizeTimeForStorage($value);
    }

    public static function formatTimeForInput(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i');
            } catch (\Exception) {
                continue;
            }
        }

        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    }

    public static function normalizeTimeForStorage(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Exception) {
                continue;
            }
        }

        return $value;
    }
}
