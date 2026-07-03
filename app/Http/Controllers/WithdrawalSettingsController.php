<?php

namespace App\Http\Controllers;

use App\Services\WithdrawalFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalSettingsController extends Controller
{
    public function __construct(
        protected WithdrawalFeeService $feeService
    ) {}

    public function edit()
    {
        $this->authorizeSuperAdmin();

        $tiers = $this->feeService->globalTiers();
        $bankTiers = $this->feeService->globalTiers(WithdrawalFeeService::CHANNEL_BANK_TRANSFER);

        return view('withdrawal.settings', compact('tiers', 'bankTiers'));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'tiers' => 'required|array|min:1',
            'tiers.*.min_amount' => 'required|integer|min:0',
            'tiers.*.max_amount' => 'nullable|integer|min:0',
            'tiers.*.charge_amount' => 'required|integer|min:0',
            'bank_tiers' => 'required|array|min:1',
            'bank_tiers.*.min_amount' => 'required|integer|min:0',
            'bank_tiers.*.max_amount' => 'nullable|integer|min:0',
            'bank_tiers.*.charge_amount' => 'required|integer|min:0',
        ]);

        $this->feeService->syncGlobalTiers($validated['tiers'], WithdrawalFeeService::CHANNEL_MOBILE_MONEY);
        $this->feeService->syncGlobalTiers($validated['bank_tiers'], WithdrawalFeeService::CHANNEL_BANK_TRANSFER);

        return redirect()
            ->route('withdrawal.settings.edit')
            ->with('success', 'Default withdrawal fee tiers updated successfully.');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only super administrators can manage withdrawal settings.');
        }
    }
}
