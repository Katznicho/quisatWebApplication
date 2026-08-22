<?php

use App\Support\CurrencyDisplay;

if (! function_exists('display_currency')) {
    function display_currency(?string $code, string $fallback = 'UGX'): string
    {
        return CurrencyDisplay::code($code, $fallback);
    }
}
