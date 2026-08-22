<?php

namespace Tests\Unit;

use App\Services\WithdrawalFeeService;
use PHPUnit\Framework\TestCase;

class WithdrawalFeeScheduleTest extends TestCase
{
    public function test_kenya_mobile_money_schedule_is_ksh_scale(): void
    {
        $mobile = WithdrawalFeeService::defaultSchedules()['KSH']['mobile_money'];

        $this->assertSame(50, $mobile[0]['min_amount']);
        $this->assertSame(50, $mobile[0]['charge_amount']);
        $this->assertSame(750, end($mobile)['charge_amount']);
        $this->assertLessThan(2000, $mobile[0]['charge_amount']);
    }

    public function test_uganda_schedule_keeps_ugx_amounts(): void
    {
        $mobile = WithdrawalFeeService::defaultSchedules()['UGX']['mobile_money'];

        $this->assertSame(500, $mobile[0]['min_amount']);
        $this->assertSame(1200, $mobile[0]['charge_amount']);
        $this->assertSame(20200, end($mobile)['charge_amount']);
    }
}
