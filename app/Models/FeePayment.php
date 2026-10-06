<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FeePayment extends Model
{
    protected $fillable = [
        'uuid',
        'fee_id',
        'parent_guardian_id',
        'amount',
        'method',
        'status',
        'receipt_number',
        'proof_path',
        'proof_url',
        'proof_mime_type',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeePayment $payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }
        });
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class);
    }

    public static function methodOptions(): array
    {
        return [
            'mtn_mobile_money' => 'MarzPay · MTN',
            'airtel_money' => 'MarzPay · Airtel',
            'card' => 'MarzPay · Card',
            'cash' => 'Cash',
            'bank_transfer' => 'Bank transfer',
            'other' => 'Other (attach receipt)',
            'mobile_money' => 'MarzPay · Mobile money',
            'school_pay' => 'School Pay',
            'sure_pay' => 'Sure Pay',
        ];
    }

    public function methodLabel(): string
    {
        return self::methodOptions()[$this->method]
            ?? ucfirst(str_replace('_', ' ', (string) $this->method));
    }
}
