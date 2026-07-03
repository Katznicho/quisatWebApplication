<?php

namespace App\Services;

use App\Models\Business;
use App\Models\WithdrawalFeeTier;
use Illuminate\Support\Collection;

class WithdrawalFeeService
{
    public const CHANNEL_MOBILE_MONEY = 'mobile_money';

    public const CHANNEL_BANK_TRANSFER = 'bank_transfer';

    public function globalTiers(string $channel = self::CHANNEL_MOBILE_MONEY): Collection
    {
        return WithdrawalFeeTier::query()
            ->whereNull('business_id')
            ->where('channel', $channel)
            ->orderBy('sort_order')
            ->get();
    }

    public function businessTiers(Business $business, string $channel = self::CHANNEL_MOBILE_MONEY): Collection
    {
        return WithdrawalFeeTier::query()
            ->where('business_id', $business->id)
            ->where('channel', $channel)
            ->orderBy('sort_order')
            ->get();
    }

    public function tiersFor(Business $business, string $channel = self::CHANNEL_MOBILE_MONEY): Collection
    {
        return $this->globalTiers($channel);
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

    public function syncGlobalTiers(array $tiers, string $channel = self::CHANNEL_MOBILE_MONEY): void
    {
        WithdrawalFeeTier::query()
            ->whereNull('business_id')
            ->where('channel', $channel)
            ->delete();

        foreach (array_values($tiers) as $index => $tier) {
            WithdrawalFeeTier::create([
                'business_id' => null,
                'channel' => $channel,
                'min_amount' => (int) $tier['min_amount'],
                'max_amount' => isset($tier['max_amount']) && $tier['max_amount'] !== '' ? (int) $tier['max_amount'] : null,
                'charge_amount' => (int) $tier['charge_amount'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function syncBusinessTiers(Business $business, array $tiers, string $channel = self::CHANNEL_MOBILE_MONEY): void
    {
        WithdrawalFeeTier::query()
            ->where('business_id', $business->id)
            ->where('channel', $channel)
            ->delete();

        foreach (array_values($tiers) as $index => $tier) {
            WithdrawalFeeTier::create([
                'business_id' => $business->id,
                'channel' => $channel,
                'min_amount' => (int) $tier['min_amount'],
                'max_amount' => isset($tier['max_amount']) && $tier['max_amount'] !== '' ? (int) $tier['max_amount'] : null,
                'charge_amount' => (int) $tier['charge_amount'],
                'sort_order' => $index + 1,
            ]);
        }
    }
}
