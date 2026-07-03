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

        $tiers = $this->feeService->globalTiers();
        $bankTiers = $this->feeService->globalTiers(WithdrawalFeeService::CHANNEL_BANK_TRANSFER);

        return view('business-wallet.index', compact(
            'business',
            'ledgers',
            'withdrawals',
            'tiers',
            'bankTiers',
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
            $withdrawal = $this->walletService->processWithdrawal(
                $business,
                (float) $validated['amount'],
                $validated['phone_number'],
                $validated['pin'],
                $validated['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Withdrawal of UGX '.number_format($withdrawal->amount, 0).' sent to '.$withdrawal->phone_number.'. Reference: '.$withdrawal->uuid);
    }

    public function withdrawToBank(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:2500',
            'bank_name' => 'required|string|max:120',
            'bank_account_number' => 'required|string|max:40',
            'bank_account_name' => 'nullable|string|max:120',
            'bank_branch' => 'nullable|string|max:120',
            'pin' => 'required|digits_between:4,6',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $withdrawal = $this->walletService->processBankWithdrawal(
                $business,
                (float) $validated['amount'],
                $validated['bank_name'],
                $validated['bank_account_number'],
                $validated['pin'],
                $validated['bank_account_name'] ?? null,
                $validated['bank_branch'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Bank transfer of UGX '.number_format($withdrawal->amount, 0).' to '.$withdrawal->bank_name.' initiated. Reference: '.$withdrawal->uuid);
    }

    public function updateTiers(Request $request)
    {
        abort(403, 'Withdrawal fee tiers are managed by the platform administrator.');
    }

    public function estimateFee(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'channel' => 'nullable|in:mobile_money,bank_transfer',
        ]);

        $amount = (float) $validated['amount'];
        $channel = $validated['channel'] ?? WithdrawalFeeService::CHANNEL_MOBILE_MONEY;
        $fee = $this->feeService->calculateFee($business, $amount, $channel);
        $availableBalance = $channel === WithdrawalFeeService::CHANNEL_BANK_TRANSFER
            ? (float) $business->card_available_balance
            : (float) $business->available_balance;

        return response()->json([
            'amount' => $amount,
            'fee' => $fee,
            'total' => $amount + $fee,
            'available_balance' => $availableBalance,
            'channel' => $channel,
        ]);
    }

    protected function authorizedBusiness(): Business
    {
        $user = Auth::user();

        if (! $user || ! $user->business_id) {
            abort(403, 'Wallet is only available for registered businesses.');
        }

        return Business::findOrFail($user->business_id);
    }
}
