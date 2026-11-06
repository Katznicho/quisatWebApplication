<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A float top-up paid in cash at the canteen and recorded by Quisat with a proof of payment.
 * The proof lives on the private "local" disk and is served through an authorised route.
 */
class CanteenFloatTopup extends Model
{
    public const PROOF_DISK = 'local';

    public const PROOF_DIRECTORY = 'pocket-pay/float-proofs';

    protected $fillable = [
        'uuid',
        'business_id',
        'canteen_id',
        'amount',
        'paid_on',
        'payment_reference',
        'proof_path',
        'proof_name',
        'note',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (CanteenFloatTopup $topup) {
            if (empty($topup->uuid)) {
                $topup->uuid = (string) Str::uuid();
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

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
