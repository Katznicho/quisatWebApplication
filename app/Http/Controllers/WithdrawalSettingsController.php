<?php

namespace App\Http\Controllers;

use App\Services\WithdrawalFeeService;
use App\Support\CurrencyDisplay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalSettingsController extends Controller
{
    public function __construct(
        protected WithdrawalFeeService $feeService
    ) {}

    public function edit(Request $request)
    {
        $this->authorizeSuperAdmin();

        $currency = $this->resolvedCurrency($request->query('currency'));
        $tiers = $this->feeService->globalTiers(WithdrawalFeeService::CHANNEL_MOBILE_MONEY, $currency);
        $bankTiers = $this->feeService->globalTiers(WithdrawalFeeService::CHANNEL_BANK_TRANSFER, $currency);

        return view('withdrawal.settings', compact('tiers', 'bankTiers', 'currency'));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'currency' => 'required|in:UGX,KSH',
            'tiers' => 'required|array|min:1',
            'tiers.*.min_amount' => 'required|integer|min:0',
            'tiers.*.max_amount' => 'nullable|integer|min:0',
            'tiers.*.charge_amount' => 'required|integer|min:0',
            'bank_tiers' => 'required|array|min:1',
            'bank_tiers.*.min_amount' => 'required|integer|min:0',
            'bank_tiers.*.max_amount' => 'nullable|integer|min:0',
            'bank_tiers.*.charge_amount' => 'required|integer|min:0',
        ]);

        $currency = $this->resolvedCurrency($validated['currency']);

        $this->feeService->syncGlobalTiers($validated['tiers'], WithdrawalFeeService::CHANNEL_MOBILE_MONEY, $currency);
        $this->feeService->syncGlobalTiers($validated['bank_tiers'], WithdrawalFeeService::CHANNEL_BANK_TRANSFER, $currency);

        return redirect()
            ->route('withdrawal.settings.edit', ['currency' => $currency])
            ->with('success', $currency.' withdrawal fee tiers updated successfully.');
    }

    protected function resolvedCurrency(?string $currency): string
    {
        return CurrencyDisplay::feeScheduleCode($currency ?: WithdrawalFeeService::DEFAULT_CURRENCY);
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only super administrators can manage withdrawal settings.');
        }
    }
}
