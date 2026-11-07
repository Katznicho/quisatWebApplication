<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PocketPayWithdrawal extends Model
{
    public const METHOD_MOBILE = 'mobile_money';

    public const METHOD_BANK = 'bank';

    protected $fillable = [
        'uuid',
        'amount',
        'method',
        'phone_number',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_branch',
        'status',
        'marz_transaction_uuid',
        'provider_reference',
        'notes',
        'requested_by',
        'canteen_id',
        'canteen_attendant_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PocketPayWithdrawal $withdrawal) {
            if (empty($withdrawal->uuid)) {
                $withdrawal->uuid = (string) Str::uuid();
            }
        });
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(CanteenAttendant::class, 'canteen_attendant_id');
    }
}
