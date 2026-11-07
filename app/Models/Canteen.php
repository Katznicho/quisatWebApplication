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

    public const MODE_FLOAT = 'float';

    public const MODE_COLLECT = 'collect';

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'location',
        'float_balance',
        'low_float_threshold',
        'status',
        'settlement_mode',
        'withdrawer_id',
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

    public function withdrawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawer_id');
    }

    public function collectsSales(): bool
    {
        return $this->settlement_mode === self::MODE_COLLECT;
    }

    public function usesFloat(): bool
    {
        return ! $this->collectsSales();
    }

    public function canBeWithdrawnBy(?User $user): bool
    {
        if (! $user || ! $this->collectsSales()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return (int) $this->withdrawer_id === (int) $user->id;
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
        return $this->usesFloat()
            && $this->low_float_threshold > 0
            && (float) $this->float_balance < $this->low_float_threshold;
    }
}
