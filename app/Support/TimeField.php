<?php

namespace App\Support;

use Carbon\Carbon;

class TimeField
{
    public const VALIDATION_RULE = 'date_format:H:i,H:i:s';

    public static function formatForInput(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i');
            } catch (\Exception) {
                continue;
            }
        }

        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    }

    public static function normalizeForStorage(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Exception) {
                continue;
            }
        }

        return $value;
    }
}
