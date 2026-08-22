<?php

namespace App\Services;

use App\Models\Business;
use App\Models\WithdrawalFeeTier;
use App\Support\CurrencyDisplay;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WithdrawalFeeService
{
    public const CHANNEL_MOBILE_MONEY = 'mobile_money';

    public const CHANNEL_BANK_TRANSFER = 'bank_transfer';

    public const DEFAULT_CURRENCY = 'UGX';

    /**
     * Uganda (UGX) remains the original MarzPay schedule.
     * Kenya (KSH) uses the same real-world fee levels, converted from UGX (~28:1) and rounded.
     *
     * @return array<string, array<string, list<array{min_amount: int, max_amount: int|null, charge_amount: int, sort_order: int}>>>
     */
    public static function defaultSchedules(): array
    {
        return [
            'UGX' => [
                self::CHANNEL_MOBILE_MONEY => [
                    ['min_amount' => 500, 'max_amount' => 40000, 'charge_amount' => 1200, 'sort_order' => 1],
                    ['min_amount' => 40001, 'max_amount' => 200000, 'charge_amount' => 1700, 'sort_order' => 2],
                    ['min_amount' => 200001, 'max_amount' => 400000, 'charge_amount' => 3000, 'sort_order' => 3],
                    ['min_amount' => 400001, 'max_amount' => 1000000, 'charge_amount' => 5200, 'sort_order' => 4],
                    ['min_amount' => 1000001, 'max_amount' => 1500000, 'charge_amount' => 15200, 'sort_order' => 5],
                    ['min_amount' => 1500001, 'max_amount' => null, 'charge_amount' => 20200, 'sort_order' => 6],
                ],
                self::CHANNEL_BANK_TRANSFER => [
                    ['min_amount' => 2500, 'max_amount' => 250000, 'charge_amount' => 5000, 'sort_order' => 1],
                    ['min_amount' => 250001, 'max_amount' => 500000, 'charge_amount' => 6000, 'sort_order' => 2],
                    ['min_amount' => 500001, 'max_amount' => 1000000, 'charge_amount' => 9000, 'sort_order' => 3],
                    ['min_amount' => 1000001, 'max_amount' => 2000000, 'charge_amount' => 13500, 'sort_order' => 4],
                    ['min_amount' => 2000001, 'max_amount' => null, 'charge_amount' => 16500, 'sort_order' => 5],
                ],
            ],
            'KSH' => [
                self::CHANNEL_MOBILE_MONEY => [
                    ['min_amount' => 50, 'max_amount' => 1500, 'charge_amount' => 50, 'sort_order' => 1],
                    ['min_amount' => 1501, 'max_amount' => 7000, 'charge_amount' => 70, 'sort_order' => 2],
                    ['min_amount' => 7001, 'max_amount' => 15000, 'charge_amount' => 110, 'sort_order' => 3],
                    ['min_amount' => 15001, 'max_amount' => 35000, 'charge_amount' => 190, 'sort_order' => 4],
                    ['min_amount' => 35001, 'max_amount' => 55000, 'charge_amount' => 550, 'sort_order' => 5],
                    ['min_amount' => 55001, 'max_amount' => null, 'charge_amount' => 750, 'sort_order' => 6],
                ],
                self::CHANNEL_BANK_TRANSFER => [
                    ['min_amount' => 100, 'max_amount' => 9000, 'charge_amount' => 180, 'sort_order' => 1],
                    ['min_amount' => 9001, 'max_amount' => 18000, 'charge_amount' => 220, 'sort_order' => 2],
                    ['min_amount' => 18001, 'max_amount' => 36000, 'charge_amount' => 330, 'sort_order' => 3],
                    ['min_amount' => 36001, 'max_amount' => 72000, 'charge_amount' => 490, 'sort_order' => 4],
                    ['min_amount' => 72001, 'max_amount' => null, 'charge_amount' => 600, 'sort_order' => 5],
                ],
            ],
        ];
    }

    public function currencyFor(Business $business): string
    {
        return CurrencyDisplay::feeScheduleCode($business->currency_code);
    }

    public function globalTiers(string $channel = self::CHANNEL_MOBILE_MONEY, ?string $currency = null): Collection
    {
        $currency = CurrencyDisplay::feeScheduleCode($currency ?: self::DEFAULT_CURRENCY);

        if (! Schema::hasTable('withdrawal_fee_tiers')) {
            return $this->defaultTiersCollection($channel, $currency);
        }

        $query = WithdrawalFeeTier::query()
            ->whereNull('business_id')
            ->where('channel', $channel)
            ->orderBy('sort_order');

        if (Schema::hasColumn('withdrawal_fee_tiers', 'currency_code')) {
            $query->where('currency_code', $currency);
        } elseif ($currency === 'KSH') {
            return $this->defaultTiersCollection($channel, $currency);
        }

        $tiers = $query->get();

        if ($tiers->isNotEmpty()) {
            return $tiers;
        }

        return $this->defaultTiersCollection($channel, $currency);
    }

    /**
     * @return Collection<int, WithdrawalFeeTier>
     */
    protected function defaultTiersCollection(string $channel, string $currency): Collection
    {
        $rows = self::defaultSchedules()[$currency][$channel] ?? [];

        return collect($rows)->map(function (array $tier) use ($channel, $currency) {
            return new WithdrawalFeeTier(array_merge($tier, [
                'business_id' => null,
                'channel' => $channel,
                'currency_code' => $currency,
            ]));
        });
    }

    public function businessTiers(Business $business, string $channel = self::CHANNEL_MOBILE_MONEY): Collection
    {
        return WithdrawalFeeTier::query()
            ->where('business_id', $business->id)
            ->where('channel', $channel)
            ->where('currency_code', $this->currencyFor($business))
            ->orderBy('sort_order')
            ->get();
    }

    public function tiersFor(Business $business, string $channel = self::CHANNEL_MOBILE_MONEY): Collection
    {
        return $this->globalTiers($channel, $this->currencyFor($business));
    }

    public function minimumAmount(Business $business, string $channel = self::CHANNEL_MOBILE_MONEY): int
    {
        $first = $this->tiersFor($business, $channel)->first();

        if ($first) {
            return max(1, (int) $first->min_amount);
        }

        return $channel === self::CHANNEL_BANK_TRANSFER ? 2500 : 500;
    }

    public function calculateFee(Business $business, float $amount, string $channel = self::CHANNEL_MOBILE_MONEY): float
    {
        $amount = max(0, $amount);
        $tiers = $this->tiersFor($business, $channel);

        foreach ($tiers as $tier) {
            $matchesMin = $amount >= (float) $tier->min_amount;
            $matchesMax = $tier->max_amount === null || $amount <= (float) $tier->max_amount;

            if ($matchesMin && $matchesMax) {
                return (float) $tier->charge_amount;
            }
        }

        $lastTier = $tiers->last();

        return $lastTier ? (float) $lastTier->charge_amount : 0;
    }

    public function syncGlobalTiers(array $tiers, string $channel = self::CHANNEL_MOBILE_MONEY, ?string $currency = null): void
    {
        $currency = CurrencyDisplay::feeScheduleCode($currency ?: self::DEFAULT_CURRENCY);

        WithdrawalFeeTier::query()
            ->whereNull('business_id')
            ->where('channel', $channel)
            ->where('currency_code', $currency)
            ->delete();

        foreach (array_values($tiers) as $index => $tier) {
            WithdrawalFeeTier::create([
                'business_id' => null,
                'channel' => $channel,
                'currency_code' => $currency,
                'min_amount' => (int) $tier['min_amount'],
                'max_amount' => isset($tier['max_amount']) && $tier['max_amount'] !== '' ? (int) $tier['max_amount'] : null,
                'charge_amount' => (int) $tier['charge_amount'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function syncBusinessTiers(Business $business, array $tiers, string $channel = self::CHANNEL_MOBILE_MONEY): void
    {
        $currency = $this->currencyFor($business);

        WithdrawalFeeTier::query()
            ->where('business_id', $business->id)
            ->where('channel', $channel)
            ->where('currency_code', $currency)
            ->delete();

        foreach (array_values($tiers) as $index => $tier) {
            WithdrawalFeeTier::create([
                'business_id' => $business->id,
                'channel' => $channel,
                'currency_code' => $currency,
                'min_amount' => (int) $tier['min_amount'],
                'max_amount' => isset($tier['max_amount']) && $tier['max_amount'] !== '' ? (int) $tier['max_amount'] : null,
                'charge_amount' => (int) $tier['charge_amount'],
                'sort_order' => $index + 1,
            ]);
        }
    }
}
