<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Canteen extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'location',
        'float_balance',
        'low_float_threshold',
        'status',
        'created_by',
    ];

    protected $casts = [
        'float_balance' => 'decimal:2',
        'low_float_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Canteen $canteen) {
            if (empty($canteen->uuid)) {
                $canteen->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function attendants(): HasMany
    {
        return $this->hasMany(CanteenAttendant::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(CanteenSale::class);
    }

    public function floatTransactions(): HasMany
    {
        return $this->hasMany(CanteenFloatTransaction::class);
    }

    public function floatTopups(): HasMany
    {
        return $this->hasMany(CanteenFloatTopup::class);
    }

    public function topups(): MorphMany
    {
        return $this->morphMany(PocketPayTopup::class, 'topupable');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFloatLow(): bool
    {
        return $this->low_float_threshold > 0 && (float) $this->float_balance < $this->low_float_threshold;
    }
}
