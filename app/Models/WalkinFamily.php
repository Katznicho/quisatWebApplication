<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WalkinFamily extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'guardian_name',
        'phone',
        'phone_digits',
        'relationship',
        'collector_name',
        'participation_consent',
        'follow_up_consent',
        'consent_version',
        'consented_at',
        'consented_by',
    ];

    protected $casts = [
        'participation_consent' => 'boolean',
        'follow_up_consent' => 'boolean',
        'consented_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $family) {
            $family->uuid ??= (string) Str::uuid();
        });
    }

    public function children(): HasMany
    {
        return $this->hasMany(WalkinChild::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(WalkinVisit::class);
    }

    public function consentedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consented_by');
    }
}
