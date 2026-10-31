<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarzSmsSetting extends Model
{
    protected $table = 'marzsms_settings';

    protected $fillable = [
        'is_enabled',
        'base_url',
        'api_key',
        'api_secret',
        'low_balance_threshold',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'low_balance_threshold' => 'integer',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => false,
            'base_url' => config('marzsms.base_url'),
            'low_balance_threshold' => 5000,
        ]);
    }

    public function hasCredentials(): bool
    {
        return filled($this->base_url) && filled($this->api_key) && filled($this->api_secret);
    }

    public function isReady(): bool
    {
        return $this->is_enabled && $this->hasCredentials();
    }

    /**
     * Masked key for display, e.g. "mk_l…9f3a".
     */
    public function maskedKey(): ?string
    {
        $key = (string) $this->api_key;

        if ($key === '') {
            return null;
        }

        return strlen($key) <= 8 ? str_repeat('•', strlen($key)) : substr($key, 0, 4).'…'.substr($key, -4);
    }
}
