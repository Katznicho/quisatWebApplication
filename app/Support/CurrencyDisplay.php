<?php

namespace App\Support;

class CurrencyDisplay
{
    /**
     * Currency labels shown in the admin UI.
     * Kenya uses KSH locally; KHS is a common typo and KES is the ISO code.
     *
     * @var array<string, string>
     */
    protected const DISPLAY_ALIASES = [
        'KES' => 'KSH',
        'KHS' => 'KSH',
    ];

    public static function code(?string $code, string $fallback = 'UGX'): string
    {
        $normalized = strtoupper(trim((string) $code));

        if ($normalized === '') {
            return strtoupper($fallback);
        }

        return self::DISPLAY_ALIASES[$normalized] ?? $normalized;
    }

    /**
     * Persist a currency code, correcting only the KHS typo.
     * ISO codes such as KES are left unchanged.
     */
    public static function storedCode(?string $code, string $fallback = 'UGX'): string
    {
        $normalized = strtoupper(trim((string) $code));

        if ($normalized === '') {
            return strtoupper($fallback);
        }

        return $normalized === 'KHS' ? 'KSH' : $normalized;
    }

    /**
     * Supported withdrawal-fee schedules. Kenya maps to KSH; everything else uses UGX.
     */
    public static function feeScheduleCode(?string $code): string
    {
        return self::code($code) === 'KSH' ? 'KSH' : 'UGX';
    }
}
