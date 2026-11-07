<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
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
        return static::query()->getConnection()->transaction(function () {
            $primary = static::query()->orderBy('id')->lockForUpdate()->first();

            if (! $primary) {
                return static::query()->create([]);
            }

            static::query()
                ->where('id', '!=', $primary->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function (self $other) use ($primary) {
                    if (! $primary->hasPayCredentials() && $other->hasPayCredentials()) {
                        $primary->marzpay_api_key = $other->marzpay_api_key;
                        $primary->marzpay_api_secret = $other->marzpay_api_secret;
                        if (filled($other->marzpay_base_url)) {
                            $primary->marzpay_base_url = $other->marzpay_base_url;
                        }
                    }

                    if (! $primary->hasWebhookSecret() && $other->hasWebhookSecret()) {
                        $primary->marzpay_webhook_secret = $other->marzpay_webhook_secret;
                    }

                    $other->delete();
                });

            if ($primary->isDirty()) {
                $primary->save();
            }

            return $primary;
        });
    }

    public function hasPayCredentials(): bool
    {
        return $this->plain('marzpay_api_key') !== null && $this->plain('marzpay_api_secret') !== null;
    }

    public function credentialsUnreadable(): bool
    {
        foreach (['marzpay_api_key', 'marzpay_api_secret'] as $attribute) {
            if (filled($this->getRawOriginal($attribute)) && $this->plain($attribute) === null) {
                return true;
            }
        }

        return false;
    }

    private function plain(string $attribute): ?string
    {
        try {
            $value = $this->getAttribute($attribute);
        } catch (DecryptException) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function payBaseUrl(): string
    {
        $url = trim((string) $this->marzpay_base_url);

        return $url !== '' ? $url : (string) config('marzpay.base_url');
    }

    public function hasWebhookSecret(): bool
    {
        return $this->plain('marzpay_webhook_secret') !== null;
    }

    public function maskedPayKey(): ?string
    {
        $key = $this->plain('marzpay_api_key');

        if ($key === null) {
            return null;
        }

        return strlen($key) <= 8 ? str_repeat('•', strlen($key)) : substr($key, 0, 4).'…'.substr($key, -4);
    }
}
