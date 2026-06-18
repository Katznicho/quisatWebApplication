<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PaymentCollection extends Model
{
    protected $fillable = [
        'reference',
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'method',
        'phone_number',
        'country',
        'status',
        'marz_transaction_uuid',
        'provider',
        'provider_transaction_id',
        'redirect_url',
        'description',
        'request_payload',
        'callback_payload',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'request_payload' => 'array',
        'callback_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentCollection $collection) {
            if (empty($collection->reference)) {
                $collection->reference = (string) Str::uuid();
            }
        });
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled'], true);
    }
}
