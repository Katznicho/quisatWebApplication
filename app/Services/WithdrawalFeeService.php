<?php

namespace App\Services;

use App\Models\Business;
use App\Models\WithdrawalFeeTier;
use Illuminate\Support\Collection;

class WithdrawalFeeService
{
    public function globalTiers(): Collection
    {
        return WithdrawalFeeTier::query()
            ->whereNull('business_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function businessTiers(Business $business): Collection
    {
        return WithdrawalFeeTier::query()
            ->where('business_id', $business->id)
            ->orderBy('sort_order')
            ->get();
    }

    public function tiersFor(Business $business): Collection
    {
        return $this->globalTiers();
    }

    public function calculateFee(Business $business, float $amount): float
    {
        $amount = max(0, $amount);
        $tiers = $this->tiersFor($business);

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

    public function syncGlobalTiers(array $tiers): void
    {
        WithdrawalFeeTier::query()->whereNull('business_id')->delete();

        foreach (array_values($tiers) as $index => $tier) {
            WithdrawalFeeTier::create([
                'business_id' => null,
                'min_amount' => (int) $tier['min_amount'],
                'max_amount' => isset($tier['max_amount']) && $tier['max_amount'] !== '' ? (int) $tier['max_amount'] : null,
                'charge_amount' => (int) $tier['charge_amount'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function syncBusinessTiers(Business $business, array $tiers): void
    {
        WithdrawalFeeTier::query()->where('business_id', $business->id)->delete();

        foreach (array_values($tiers) as $index => $tier) {
            WithdrawalFeeTier::create([
                'business_id' => $business->id,
                'min_amount' => (int) $tier['min_amount'],
                'max_amount' => isset($tier['max_amount']) && $tier['max_amount'] !== '' ? (int) $tier['max_amount'] : null,
                'charge_amount' => (int) $tier['charge_amount'],
                'sort_order' => $index + 1,
            ]);
        }
    }
}
