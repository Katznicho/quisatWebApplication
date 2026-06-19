<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarzPaySetting extends Model
{
    protected $table = 'marzpay_settings';

    protected $fillable = [
        'mobile_money_charge_type',
        'mobile_money_charge_value',
        'card_charge_type',
        'card_charge_value',
    ];

    protected $casts = [
        'mobile_money_charge_value' => 'decimal:2',
        'card_charge_value' => 'decimal:2',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'mobile_money_charge_type' => 'fixed',
            'mobile_money_charge_value' => 0,
            'card_charge_type' => 'fixed',
            'card_charge_value' => 0,
        ]);
    }

    /**
     * @return array{base_amount:int, platform_charge:int, total_amount:int}
     */
    public function calculateCharge(int $baseAmount, string $method): array
    {
        $baseAmount = max(0, $baseAmount);

        if ($method === 'card') {
            $type = $this->card_charge_type;
            $value = (float) $this->card_charge_value;
        } else {
            $type = $this->mobile_money_charge_type;
            $value = (float) $this->mobile_money_charge_value;
        }

        $platformCharge = $type === 'percent'
            ? (int) round($baseAmount * ($value / 100))
            : (int) round($value);

        $platformCharge = max(0, $platformCharge);

        return [
            'base_amount' => $baseAmount,
            'platform_charge' => $platformCharge,
            'total_amount' => $baseAmount + $platformCharge,
        ];
    }
}
