<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class BusinessBalanceLedger extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'type',
        'amount',
        'available_balance_after',
        'total_balance_after',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'available_balance_after' => 'decimal:2',
        'total_balance_after' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (BusinessBalanceLedger $ledger) {
            if (empty($ledger->uuid)) {
                $ledger->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
