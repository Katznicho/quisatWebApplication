<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Logs into the Quisat Attendant app with phone + 4-digit PIN.
 */
class CanteenAttendant extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, SoftDeletes;

    public const STATUS_PENDING_SETUP = 'pending_setup';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'uuid',
        'business_id',
        'canteen_id',
        'name',
        'phone',
        'status',
        'can_withdraw',
        'created_by',
    ];

    protected $hidden = [
        'pin',
        'otp_hash',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'can_withdraw' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CanteenAttendant $attendant) {
            if (empty($attendant->uuid)) {
                $attendant->uuid = (string) Str::uuid();
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

    public function sales(): HasMany
    {
        return $this->hasMany(CanteenSale::class);
    }

    public function getAuthPassword(): ?string
    {
        return $this->pin;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
