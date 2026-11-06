<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Marz Pay credentials for Quisat Pocket Pay.
 * SMS and charges stay on the accounts the rest of Quisat already uses.
 */
class PocketPayGatewaySetting extends Model
{
    protected $fillable = [
        'marzpay_base_url',
        'marzpay_api_key',
        'marzpay_api_secret',
        'marzpay_webhook_secret',
    ];

    protected $casts = [
        'marzpay_api_key' => 'encrypted',
        'marzpay_api_secret' => 'encrypted',
        'marzpay_webhook_secret' => 'encrypted',
    ];

    protected $hidden = [
        'marzpay_api_key',
        'marzpay_api_secret',
        'marzpay_webhook_secret',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function hasPayCredentials(): bool
    {
        return filled($this->marzpay_base_url)
            && filled($this->marzpay_api_key)
            && filled($this->marzpay_api_secret);
    }

    public function hasWebhookSecret(): bool
    {
        return filled($this->marzpay_webhook_secret);
    }

    public function maskedPayKey(): ?string
    {
        $key = (string) $this->marzpay_api_key;

        if ($key === '') {
            return null;
        }

        return strlen($key) <= 8 ? str_repeat('•', strlen($key)) : substr($key, 0, 4).'…'.substr($key, -4);
    }
}
