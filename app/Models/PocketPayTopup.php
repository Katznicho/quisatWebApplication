<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
use App\Services\PocketPayService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Mobile money top-up of a student card or a canteen float, collected through MarzPay.
 */
class PocketPayTopup extends Model
{
    use InteractsWithMarzPay;

    protected $fillable = [
        'uuid',
        'business_id',
        'topupable_type',
        'topupable_id',
        'amount',
        'phone_number',
        'status',
        'initiated_by_type',
        'initiated_by_id',
        'credited_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credited_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PocketPayTopup $topup) {
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

    public function topupable(): MorphTo
    {
        return $this->morphTo();
    }

    public function initiatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function isForCard(): bool
    {
        return $this->topupable_type === PocketPayCard::class;
    }

    public function marzPayAmount(): int
    {
        return (int) round((float) $this->amount);
    }

    public function marzPayDescription(): string
    {
        $target = $this->topupable;

        if ($target instanceof PocketPayCard) {
            return 'Pocket Pay top-up: '.($target->student?->full_name ?? $target->card_number);
        }

        return 'Canteen float top-up: '.($target?->name ?? 'canteen');
    }

    public function marzPayPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function markMarzPayCompleted(PaymentCollection $collection): void
    {
        app(PocketPayService::class)->creditTopup($this);
    }

    public function markMarzPayFailed(PaymentCollection $collection): void
    {
        if ($this->credited_at === null) {
            $this->update(['status' => 'failed']);
        }
    }
}
