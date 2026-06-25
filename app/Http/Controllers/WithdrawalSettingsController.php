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

        return view('withdrawal.settings', compact('tiers'));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'tiers' => 'required|array|min:1',
            'tiers.*.min_amount' => 'required|integer|min:0',
            'tiers.*.max_amount' => 'nullable|integer|min:0',
            'tiers.*.charge_amount' => 'required|integer|min:0',
        ]);

        $this->feeService->syncGlobalTiers($validated['tiers']);

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
