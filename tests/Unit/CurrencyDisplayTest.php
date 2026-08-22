<?php

namespace Tests\Unit;

use App\Support\CurrencyDisplay;
use PHPUnit\Framework\TestCase;

class CurrencyDisplayTest extends TestCase
{
    public function test_it_maps_kenya_codes_to_ksh(): void
    {
        $this->assertSame('KSH', CurrencyDisplay::code('KHS'));
        $this->assertSame('KSH', CurrencyDisplay::code('kes'));
        $this->assertSame('KSH', CurrencyDisplay::code('KSH'));
    }

    public function test_it_keeps_other_currencies_and_falls_back(): void
    {
        $this->assertSame('UGX', CurrencyDisplay::code('UGX'));
        $this->assertSame('TZS', CurrencyDisplay::code('TZS'));
        $this->assertSame('UGX', CurrencyDisplay::code(null));
        $this->assertSame('UGX', CurrencyDisplay::code(''));
    }

    public function test_it_corrects_only_the_khs_typo_when_storing(): void
    {
        $this->assertSame('KSH', CurrencyDisplay::storedCode('KHS'));
        $this->assertSame('KES', CurrencyDisplay::storedCode('KES'));
        $this->assertSame('UGX', CurrencyDisplay::storedCode(null));
    }

    public function test_fee_schedule_maps_kenya_to_ksh_and_others_to_ugx(): void
    {
        $this->assertSame('KSH', CurrencyDisplay::feeScheduleCode('KES'));
        $this->assertSame('KSH', CurrencyDisplay::feeScheduleCode('KHS'));
        $this->assertSame('KSH', CurrencyDisplay::feeScheduleCode('KSH'));
        $this->assertSame('UGX', CurrencyDisplay::feeScheduleCode('UGX'));
        $this->assertSame('UGX', CurrencyDisplay::feeScheduleCode('TZS'));
        $this->assertSame('UGX', CurrencyDisplay::feeScheduleCode(null));
    }
}
