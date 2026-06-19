<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessBalanceLedger;
use App\Models\WithdrawalRequest;
use App\Services\BusinessWalletService;
use App\Services\WithdrawalFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BusinessWalletController extends Controller
{
    public function __construct(
        protected BusinessWalletService $walletService,
        protected WithdrawalFeeService $feeService
    ) {}

    public function index()
    {
        $business = $this->authorizedBusiness();

        $ledgers = BusinessBalanceLedger::query()
            ->where('business_id', $business->id)
            ->latest()
            ->limit(20)
            ->get();

        $withdrawals = WithdrawalRequest::query()
            ->where('business_id', $business->id)
            ->latest()
            ->limit(10)
            ->get();

        $tiers = $this->feeService->tiersFor($business);
        $customTiers = $this->feeService->businessTiers($business);
        $globalTiers = $this->feeService->globalTiers();

        return view('business-wallet.index', compact(
            'business',
            'ledgers',
            'withdrawals',
            'tiers',
            'customTiers',
            'globalTiers'
        ));
    }

    public function setupPin(Request $request)
    {
        $business = $this->authorizedBusiness();

        if ($this->walletService->hasPin($business)) {
            return back()->with('error', 'A withdrawal PIN is already set. Use change PIN instead.');
        }

        $validated = $request->validate([
            'pin' => 'required|digits_between:4,6|confirmed',
        ]);

        $this->walletService->setPin($business, $validated['pin']);

        return back()->with('success', 'Withdrawal PIN set successfully.');
    }

    public function changePin(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'current_pin' => 'required|digits_between:4,6',
            'pin' => 'required|digits_between:4,6|confirmed',
        ]);

        try {
            $this->walletService->changePin($business, $validated['current_pin'], $validated['pin']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Withdrawal PIN updated successfully.');
    }

    public function resetPin(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'password' => 'required|string',
            'pin' => 'required|digits_between:4,6|confirmed',
        ]);

        try {
            $this->walletService->resetPin($business, $validated['password'], $validated['pin']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Withdrawal PIN reset successfully.');
    }

    public function withdraw(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:500',
            'phone_number' => 'required|string|max:20',
            'pin' => 'required|digits_between:4,6',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $withdrawal = $this->walletService->requestWithdrawal(
                $business,
                (float) $validated['amount'],
                $validated['phone_number'],
                $validated['pin'],
                $validated['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Withdrawal request submitted. Reference: '.$withdrawal->uuid);
    }

    public function updateTiers(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'use_custom_withdrawal_tiers' => 'nullable|boolean',
            'tiers' => 'nullable|array|min:1',
            'tiers.*.min_amount' => 'required_with:tiers|integer|min:0',
            'tiers.*.max_amount' => 'nullable|integer|min:0',
            'tiers.*.charge_amount' => 'required_with:tiers|integer|min:0',
        ]);

        $useCustom = $request->boolean('use_custom_withdrawal_tiers');
        $business->update(['use_custom_withdrawal_tiers' => $useCustom]);

        if ($useCustom && ! empty($validated['tiers'])) {
            $this->feeService->syncBusinessTiers($business, $validated['tiers']);
        }

        return back()->with('success', 'Withdrawal fee tiers updated successfully.');
    }

    public function estimateFee(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = (float) $validated['amount'];
        $fee = $this->feeService->calculateFee($business, $amount);

        return response()->json([
            'amount' => $amount,
            'fee' => $fee,
            'total' => $amount + $fee,
            'available_balance' => (float) $business->available_balance,
        ]);
    }

    protected function authorizedBusiness(): Business
    {
        $user = Auth::user();

        if (! $user || ! $user->business_id || (int) $user->business_id === 1) {
            abort(403, 'Wallet is only available for registered businesses.');
        }

        return Business::findOrFail($user->business_id);
    }
}
