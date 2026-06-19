<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessBalanceLedger;
use App\Models\Order;
use App\Models\PaymentCollection;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BusinessWalletService
{
    public function __construct(
        protected WithdrawalFeeService $feeService
    ) {}

    public function hasPin(Business $business): bool
    {
        return ! empty($business->withdrawal_pin);
    }

    public function setPin(Business $business, string $pin): void
    {
        $this->validatePinFormat($pin);

        $business->update([
            'withdrawal_pin' => Hash::make($pin),
        ]);
    }

    public function verifyPin(Business $business, string $pin): bool
    {
        if (! $this->hasPin($business)) {
            return false;
        }

        return Hash::check($pin, $business->withdrawal_pin);
    }

    public function changePin(Business $business, string $currentPin, string $newPin): void
    {
        if (! $this->verifyPin($business, $currentPin)) {
            throw ValidationException::withMessages([
                'current_pin' => 'The current withdrawal PIN is incorrect.',
            ]);
        }

        $this->setPin($business, $newPin);
    }

    public function resetPin(Business $business, string $accountPassword, string $newPin): void
    {
        $user = auth()->user();

        if (! $user || ! Hash::check($accountPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Your account password is incorrect.',
            ]);
        }

        $this->setPin($business, $newPin);
    }

    public function creditFromCollection(Business $business, PaymentCollection $collection): void
    {
        if ($collection->business_credited_at || (int) $business->id === 1) {
            return;
        }

        $payable = $collection->payable;

        if ($payable instanceof Order) {
            $this->creditHeldForOrder($business, $collection, $payable);

            return;
        }

        $this->creditAvailableFromCollection($business, $collection);
    }

    public function creditHeldForOrder(Business $business, PaymentCollection $collection, Order $order): void
    {
        if ($collection->business_credited_at || (int) $business->id === 1) {
            return;
        }

        $amount = (float) ($collection->base_amount ?? 0);

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($business, $collection, $order, $amount) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            $lockedBusiness->total_balance = (float) $lockedBusiness->total_balance + $amount;
            $lockedBusiness->held_balance = (float) $lockedBusiness->held_balance + $amount;
            $lockedBusiness->save();

            $order->update([
                'wallet_credit_amount' => $amount,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'pending_credit',
                'amount' => $amount,
                'available_balance_after' => $lockedBusiness->available_balance,
                'total_balance_after' => $lockedBusiness->total_balance,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => 'Online payment held pending delivery: '.$order->order_number,
                'created_by' => null,
            ]);

            $collection->update([
                'business_id' => $lockedBusiness->id,
                'business_credited_at' => now(),
            ]);
        });
    }

    public function releaseOrderFunds(Order $order, ?int $releasedBy = null): bool
    {
        if ($order->funds_released_at || $order->payment_status !== 'paid') {
            return false;
        }

        $amount = (float) ($order->wallet_credit_amount ?? $this->resolveOrderCreditAmount($order));

        if ($amount <= 0) {
            return false;
        }

        DB::transaction(function () use ($order, $amount, $releasedBy) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($order->business_id);

            if ((float) $lockedBusiness->held_balance < $amount) {
                throw ValidationException::withMessages([
                    'order' => 'Insufficient held balance to release funds for this order.',
                ]);
            }

            $lockedBusiness->held_balance = (float) $lockedBusiness->held_balance - $amount;
            $lockedBusiness->available_balance = (float) $lockedBusiness->available_balance + $amount;
            $lockedBusiness->save();

            $order->update([
                'wallet_credit_amount' => $amount,
                'funds_released_at' => now(),
                'funds_released_by' => $releasedBy,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'fund_release',
                'amount' => $amount,
                'available_balance_after' => $lockedBusiness->available_balance,
                'total_balance_after' => $lockedBusiness->total_balance,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => 'Order received — funds released: '.$order->order_number,
                'created_by' => $releasedBy,
            ]);
        });

        return true;
    }

    protected function creditAvailableFromCollection(Business $business, PaymentCollection $collection): void
    {
        $amount = (float) ($collection->base_amount ?? 0);

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($business, $collection, $amount) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            $lockedBusiness->available_balance = (float) $lockedBusiness->available_balance + $amount;
            $lockedBusiness->total_balance = (float) $lockedBusiness->total_balance + $amount;
            $lockedBusiness->save();

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'credit',
                'amount' => $amount,
                'available_balance_after' => $lockedBusiness->available_balance,
                'total_balance_after' => $lockedBusiness->total_balance,
                'reference_type' => PaymentCollection::class,
                'reference_id' => $collection->id,
                'description' => 'Online payment received: '.$collection->description,
                'created_by' => null,
            ]);

            $collection->update([
                'business_id' => $lockedBusiness->id,
                'business_credited_at' => now(),
            ]);
        });
    }

    protected function resolveOrderCreditAmount(Order $order): float
    {
        $collection = PaymentCollection::query()
            ->where('payable_type', Order::class)
            ->where('payable_id', $order->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        return (float) ($collection?->base_amount ?? 0);
    }

    public function requestWithdrawal(
        Business $business,
        float $amount,
        string $phoneNumber,
        string $pin,
        ?string $notes = null
    ): WithdrawalRequest {
        if (! $this->hasPin($business)) {
            throw ValidationException::withMessages([
                'pin' => 'Please set up a withdrawal PIN before requesting a withdrawal.',
            ]);
        }

        if (! $this->verifyPin($business, $pin)) {
            throw ValidationException::withMessages([
                'pin' => 'The withdrawal PIN is incorrect.',
            ]);
        }

        if ($amount < 500) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum withdrawal amount is UGX 500.',
            ]);
        }

        $fee = $this->feeService->calculateFee($business, $amount);
        $totalDebited = $amount + $fee;

        return DB::transaction(function () use ($business, $amount, $fee, $totalDebited, $phoneNumber, $notes) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            if ((float) $lockedBusiness->available_balance < $totalDebited) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient available balance for this withdrawal (including fees).',
                ]);
            }

            $lockedBusiness->available_balance = (float) $lockedBusiness->available_balance - $totalDebited;
            $lockedBusiness->save();

            $withdrawal = WithdrawalRequest::create([
                'business_id' => $lockedBusiness->id,
                'requested_by' => auth()->id(),
                'amount' => $amount,
                'fee_amount' => $fee,
                'total_debited' => $totalDebited,
                'phone_number' => $phoneNumber,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'debit',
                'amount' => $amount,
                'available_balance_after' => $lockedBusiness->available_balance,
                'total_balance_after' => $lockedBusiness->total_balance,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $withdrawal->id,
                'description' => 'Withdrawal request to '.$phoneNumber,
                'created_by' => auth()->id(),
            ]);

            if ($fee > 0) {
                BusinessBalanceLedger::create([
                    'business_id' => $lockedBusiness->id,
                    'type' => 'withdrawal_fee',
                    'amount' => $fee,
                    'available_balance_after' => $lockedBusiness->available_balance,
                    'total_balance_after' => $lockedBusiness->total_balance,
                    'reference_type' => WithdrawalRequest::class,
                    'reference_id' => $withdrawal->id,
                    'description' => 'Withdrawal processing fee',
                    'created_by' => auth()->id(),
                ]);
            }

            return $withdrawal;
        });
    }

    protected function validatePinFormat(string $pin): void
    {
        if (! preg_match('/^\d{4,6}$/', $pin)) {
            throw ValidationException::withMessages([
                'pin' => 'Withdrawal PIN must be 4 to 6 digits.',
            ]);
        }
    }
}
