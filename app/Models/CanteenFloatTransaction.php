<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class CanteenFloatTransaction extends Model
{
    protected $fillable = [
        'uuid',
        'canteen_id',
        'business_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CanteenFloatTransaction $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
