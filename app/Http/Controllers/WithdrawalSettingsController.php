<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
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

    public function withdrawals()
    {
        $this->authorizeSuperAdmin();

        $withdrawals = WithdrawalRequest::query()
            ->with(['business', 'requestedBy'])
            ->latest()
            ->paginate(25);

        return view('withdrawal.requests', compact('withdrawals'));
    }

    public function updateWithdrawalStatus(Request $request, WithdrawalRequest $withdrawal)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,failed,cancelled',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $withdrawal->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $withdrawal->admin_notes,
            'processed_at' => in_array($validated['status'], ['completed', 'failed', 'cancelled'], true)
                ? now()
                : $withdrawal->processed_at,
        ]);

        return back()->with('success', 'Withdrawal request updated.');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only super administrators can manage withdrawal settings.');
        }
    }
}
