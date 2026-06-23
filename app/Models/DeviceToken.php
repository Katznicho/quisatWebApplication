<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class DeviceToken extends Model
{
    protected $fillable = [
        'uuid',
        'tokenable_type',
        'tokenable_id',
        'device_id',
        'push_token',
        'platform',
        'device_name',
        'app_version',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DeviceToken $token) {
            if (empty($token->uuid)) {
                $token->uuid = (string) Str::uuid();
            }
        });
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isWeb(): bool
    {
        return $this->platform === 'web';
    }

    public function isExpo(): bool
    {
        return in_array($this->platform, ['ios', 'android'], true);
    }
}
