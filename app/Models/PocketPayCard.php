<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PocketPayCard extends Model
{
    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'uuid',
        'business_id',
        'student_id',
        'card_number',
        'status',
        'daily_limit',
        'assigned_by',
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'locked_until' => 'datetime',
        'activated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PocketPayCard $card) {
            if (empty($card->uuid)) {
                $card->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (PocketPayCard $card) {
            $card->card_number = static::normalizeNumber($card->card_number);
        });
    }

    public static function normalizeNumber(?string $number): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', (string) $number) ?? '');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function activatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PocketPayCardTransaction::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(CanteenSale::class);
    }

    public function topups(): MorphMany
    {
        return $this->morphMany(PocketPayTopup::class, 'topupable');
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_BLOCKED => 'Deactivated',
            default => 'Not activated',
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function maskedNumber(): string
    {
        $number = (string) $this->card_number;
        $length = strlen($number);

        if ($length <= 4) {
            return $number;
        }

        // Keep the year prefix of issued numbers: 260412344821 → "26•• •••• 4821".
        if ($length >= 8) {
            $masked = mb_str_split(substr($number, 0, 2).str_repeat('•', $length - 6).substr($number, -4));

            return implode(' ', array_map('implode', array_chunk($masked, 4)));
        }

        return str_repeat('•', $length - 4).substr($number, -4);
    }
}
