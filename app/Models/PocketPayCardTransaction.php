<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PocketPayCardTransaction extends Model
{
    protected $fillable = [
        'uuid',
        'pocket_pay_card_id',
        'business_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'canteen_id',
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
        static::creating(function (PocketPayCardTransaction $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(PocketPayCard::class, 'pocket_pay_card_id');
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
