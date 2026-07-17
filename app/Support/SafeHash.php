<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SafeHash
{
    /**
     * Check a plain value against a hash without throwing on invalid algorithms.
     */
    public static function check(?string $value, ?string $hashedValue): bool
    {
        if ($value === null || $hashedValue === null || $hashedValue === '') {
            return false;
        }

        try {
            return Hash::check($value, $hashedValue);
        } catch (RuntimeException) {
            return false;
        }
    }
}
