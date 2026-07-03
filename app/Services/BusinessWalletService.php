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
    public const WALLET_MOBILE_MONEY = 'mobile_money';

    public const WALLET_CARD = 'card';
    public function __construct(
        protected WithdrawalFeeService $feeService,
        protected MarzPayService $marzPayService
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

        $walletChannel = $this->walletChannelForCollection($collection);

        DB::transaction(function () use ($business, $collection, $order, $amount, $walletChannel) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            if ($walletChannel === self::WALLET_CARD) {
                $lockedBusiness->card_total_balance = (float) $lockedBusiness->card_total_balance + $amount;
                $lockedBusiness->card_held_balance = (float) $lockedBusiness->card_held_balance + $amount;
            } else {
                $lockedBusiness->total_balance = (float) $lockedBusiness->total_balance + $amount;
                $lockedBusiness->held_balance = (float) $lockedBusiness->held_balance + $amount;
            }
            $lockedBusiness->save();

            $order->update([
                'wallet_credit_amount' => $amount,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'pending_credit',
                'wallet_channel' => $walletChannel,
                'amount' => $amount,
                'available_balance_after' => $walletChannel === self::WALLET_CARD
                    ? (float) $lockedBusiness->card_available_balance
                    : (float) $lockedBusiness->available_balance,
                'total_balance_after' => $walletChannel === self::WALLET_CARD
                    ? (float) $lockedBusiness->card_total_balance
                    : (float) $lockedBusiness->total_balance,
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

        $walletChannel = $this->walletChannelForOrder($order);

        DB::transaction(function () use ($order, $amount, $releasedBy, $walletChannel) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($order->business_id);

            if ($walletChannel === self::WALLET_CARD) {
                if ((float) $lockedBusiness->card_held_balance < $amount) {
                    throw ValidationException::withMessages([
                        'order' => 'Insufficient held card balance to release funds for this order.',
                    ]);
                }

                $lockedBusiness->card_held_balance = (float) $lockedBusiness->card_held_balance - $amount;
                $lockedBusiness->card_available_balance = (float) $lockedBusiness->card_available_balance + $amount;
            } else {
                if ((float) $lockedBusiness->held_balance < $amount) {
                    throw ValidationException::withMessages([
                        'order' => 'Insufficient held balance to release funds for this order.',
                    ]);
                }

                $lockedBusiness->held_balance = (float) $lockedBusiness->held_balance - $amount;
                $lockedBusiness->available_balance = (float) $lockedBusiness->available_balance + $amount;
            }
            $lockedBusiness->save();

            $order->update([
                'wallet_credit_amount' => $amount,
                'funds_released_at' => now(),
                'funds_released_by' => $releasedBy,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'fund_release',
                'wallet_channel' => $walletChannel,
                'amount' => $amount,
                'available_balance_after' => $walletChannel === self::WALLET_CARD
                    ? (float) $lockedBusiness->card_available_balance
                    : (float) $lockedBusiness->available_balance,
                'total_balance_after' => $walletChannel === self::WALLET_CARD
                    ? (float) $lockedBusiness->card_total_balance
                    : (float) $lockedBusiness->total_balance,
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

        $walletChannel = $this->walletChannelForCollection($collection);

        DB::transaction(function () use ($business, $collection, $amount, $walletChannel) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            if ($walletChannel === self::WALLET_CARD) {
                $lockedBusiness->card_available_balance = (float) $lockedBusiness->card_available_balance + $amount;
                $lockedBusiness->card_total_balance = (float) $lockedBusiness->card_total_balance + $amount;
            } else {
                $lockedBusiness->available_balance = (float) $lockedBusiness->available_balance + $amount;
                $lockedBusiness->total_balance = (float) $lockedBusiness->total_balance + $amount;
            }
            $lockedBusiness->save();

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'credit',
                'wallet_channel' => $walletChannel,
                'amount' => $amount,
                'available_balance_after' => $walletChannel === self::WALLET_CARD
                    ? (float) $lockedBusiness->card_available_balance
                    : (float) $lockedBusiness->available_balance,
                'total_balance_after' => $walletChannel === self::WALLET_CARD
                    ? (float) $lockedBusiness->card_total_balance
                    : (float) $lockedBusiness->total_balance,
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

    public function processWithdrawal(
        Business $business,
        float $amount,
        string $phoneNumber,
        string $pin,
        ?string $notes = null
    ): WithdrawalRequest {
        if (! $this->hasPin($business)) {
            throw ValidationException::withMessages([
                'pin' => 'Please set up a withdrawal PIN before withdrawing.',
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

        $fee = $this->feeService->calculateFee($business, $amount, WithdrawalFeeService::CHANNEL_MOBILE_MONEY);
        $totalDebited = $amount + $fee;

        $withdrawal = DB::transaction(function () use ($business, $amount, $fee, $totalDebited, $phoneNumber, $notes) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            if ((float) $lockedBusiness->available_balance < $totalDebited) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient mobile money wallet balance for this withdrawal (including fees).',
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
                'wallet_source' => 'main',
                'phone_number' => $phoneNumber,
                'status' => 'processing',
                'notes' => $notes,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'debit',
                'wallet_channel' => self::WALLET_MOBILE_MONEY,
                'amount' => $amount,
                'available_balance_after' => $lockedBusiness->available_balance,
                'total_balance_after' => $lockedBusiness->total_balance,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $withdrawal->id,
                'description' => 'Withdrawal to '.$phoneNumber,
                'created_by' => auth()->id(),
            ]);

            if ($fee > 0) {
                BusinessBalanceLedger::create([
                    'business_id' => $lockedBusiness->id,
                    'type' => 'withdrawal_fee',
                    'wallet_channel' => self::WALLET_MOBILE_MONEY,
                    'amount' => $fee,
                    'available_balance_after' => $lockedBusiness->available_balance,
                    'total_balance_after' => $lockedBusiness->total_balance,
                    'reference_type' => WithdrawalRequest::class,
                    'reference_id' => $withdrawal->id,
                    'description' => 'Mobile money withdrawal processing fee',
                    'created_by' => auth()->id(),
                ]);
            }

            return $withdrawal;
        });

        $result = $this->marzPayService->sendMoney(
            $withdrawal->uuid,
            (float) $withdrawal->amount,
            $withdrawal->phone_number,
            'Wallet withdrawal: '.$withdrawal->uuid
        );

        if (! $result['success']) {
            $this->refundFailedWithdrawal($withdrawal, $result['message'] ?? 'Withdrawal failed.');

            throw ValidationException::withMessages([
                'amount' => $result['message'] ?? 'Withdrawal could not be completed. Your balance has been restored.',
            ]);
        }

        $withdrawal->update([
            'status' => 'completed',
            'marz_transaction_uuid' => $result['transaction_uuid'] ?? null,
            'provider_reference' => $result['provider_reference'] ?? null,
            'processed_at' => now(),
        ]);

        return $withdrawal->fresh();
    }

    public function processBankWithdrawal(
        Business $business,
        float $amount,
        string $bankName,
        string $accountNumber,
        string $pin,
        ?string $accountName = null,
        ?string $bankBranch = null,
        ?string $notes = null,
    ): WithdrawalRequest {
        if (! $this->hasPin($business)) {
            throw ValidationException::withMessages([
                'pin' => 'Please set up a withdrawal PIN before withdrawing.',
            ]);
        }

        if (! $this->verifyPin($business, $pin)) {
            throw ValidationException::withMessages([
                'pin' => 'The withdrawal PIN is incorrect.',
            ]);
        }

        if ($amount < 2500) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum bank transfer amount is UGX 2,500.',
            ]);
        }

        $fee = $this->feeService->calculateFee($business, $amount, WithdrawalFeeService::CHANNEL_BANK_TRANSFER);
        $totalDebited = $amount + $fee;

        $withdrawal = DB::transaction(function () use (
            $business,
            $amount,
            $fee,
            $totalDebited,
            $bankName,
            $accountNumber,
            $accountName,
            $bankBranch,
            $notes
        ) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);

            if ((float) $lockedBusiness->card_available_balance < $totalDebited) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient card wallet balance for this bank transfer (including fees).',
                ]);
            }

            $lockedBusiness->card_available_balance = (float) $lockedBusiness->card_available_balance - $totalDebited;
            $lockedBusiness->save();

            $withdrawal = WithdrawalRequest::create([
                'business_id' => $lockedBusiness->id,
                'requested_by' => auth()->id(),
                'amount' => $amount,
                'fee_amount' => $fee,
                'total_debited' => $totalDebited,
                'wallet_source' => 'card',
                'bank_name' => $bankName,
                'bank_account_number' => $accountNumber,
                'bank_account_name' => $accountName,
                'bank_branch' => $bankBranch,
                'status' => 'processing',
                'notes' => $notes,
            ]);

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'debit',
                'wallet_channel' => self::WALLET_CARD,
                'amount' => $amount,
                'available_balance_after' => $lockedBusiness->card_available_balance,
                'total_balance_after' => $lockedBusiness->card_total_balance,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $withdrawal->id,
                'description' => 'Bank transfer to '.$bankName.' '.$accountNumber,
                'created_by' => auth()->id(),
            ]);

            if ($fee > 0) {
                BusinessBalanceLedger::create([
                    'business_id' => $lockedBusiness->id,
                    'type' => 'withdrawal_fee',
                    'wallet_channel' => self::WALLET_CARD,
                    'amount' => $fee,
                    'available_balance_after' => $lockedBusiness->card_available_balance,
                    'total_balance_after' => $lockedBusiness->card_total_balance,
                    'reference_type' => WithdrawalRequest::class,
                    'reference_id' => $withdrawal->id,
                    'description' => 'Bank transfer processing fee',
                    'created_by' => auth()->id(),
                ]);
            }

            return $withdrawal;
        });

        $result = $this->marzPayService->createBankTransfer(
            $withdrawal->uuid,
            (float) $withdrawal->amount,
            $withdrawal->bank_name,
            $withdrawal->bank_account_number,
            'card',
            $withdrawal->bank_account_name,
            $withdrawal->bank_branch,
            'Card wallet bank withdrawal: '.$withdrawal->uuid
        );

        if (! $result['success']) {
            $this->refundFailedWithdrawal($withdrawal, $result['message'] ?? 'Bank transfer failed.');

            throw ValidationException::withMessages([
                'amount' => $result['message'] ?? 'Bank transfer could not be completed. Your balance has been restored.',
            ]);
        }

        $providerStatus = $result['status'] ?? 'processing';
        $withdrawal->update([
            'status' => in_array($providerStatus, ['completed', 'processing', 'pending'], true) ? 'processing' : 'failed',
            'marz_transaction_uuid' => $result['transaction_uuid'] ?? $result['reference'] ?? null,
            'provider_reference' => $result['reference'] ?? null,
            'processed_at' => $providerStatus === 'completed' ? now() : null,
        ]);

        if ($providerStatus === 'completed') {
            $withdrawal->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
        }

        return $withdrawal->fresh();
    }

    public function refundFailedWithdrawal(WithdrawalRequest $withdrawal, ?string $reason = null): void
    {
        if (in_array($withdrawal->status, ['failed', 'cancelled'], true)) {
            return;
        }

        DB::transaction(function () use ($withdrawal, $reason) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($withdrawal->business_id);
            $lockedWithdrawal = WithdrawalRequest::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if (in_array($lockedWithdrawal->status, ['failed', 'cancelled'], true)) {
                return;
            }

            if ($lockedWithdrawal->wallet_source === 'card') {
                $lockedBusiness->card_available_balance = (float) $lockedBusiness->card_available_balance + (float) $lockedWithdrawal->total_debited;
            } else {
                $lockedBusiness->available_balance = (float) $lockedBusiness->available_balance + (float) $lockedWithdrawal->total_debited;
            }
            $lockedBusiness->save();

            $lockedWithdrawal->update([
                'status' => 'failed',
                'admin_notes' => $reason,
                'processed_at' => now(),
            ]);

            $walletChannel = $lockedWithdrawal->wallet_source === 'card'
                ? self::WALLET_CARD
                : self::WALLET_MOBILE_MONEY;

            BusinessBalanceLedger::create([
                'business_id' => $lockedBusiness->id,
                'type' => 'credit',
                'wallet_channel' => $walletChannel,
                'amount' => (float) $lockedWithdrawal->amount,
                'available_balance_after' => $walletChannel === self::WALLET_CARD
                    ? $lockedBusiness->card_available_balance
                    : $lockedBusiness->available_balance,
                'total_balance_after' => $walletChannel === self::WALLET_CARD
                    ? $lockedBusiness->card_total_balance
                    : $lockedBusiness->total_balance,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $lockedWithdrawal->id,
                'description' => $walletChannel === self::WALLET_CARD
                    ? 'Bank transfer refund: '.$lockedWithdrawal->bank_account_number
                    : 'Withdrawal refund: '.$lockedWithdrawal->phone_number,
                'created_by' => null,
            ]);

            if ((float) $lockedWithdrawal->fee_amount > 0) {
                BusinessBalanceLedger::create([
                    'business_id' => $lockedBusiness->id,
                    'type' => 'credit',
                    'wallet_channel' => $walletChannel,
                    'amount' => (float) $lockedWithdrawal->fee_amount,
                    'available_balance_after' => $walletChannel === self::WALLET_CARD
                        ? $lockedBusiness->card_available_balance
                        : $lockedBusiness->available_balance,
                    'total_balance_after' => $walletChannel === self::WALLET_CARD
                        ? $lockedBusiness->card_total_balance
                        : $lockedBusiness->total_balance,
                    'reference_type' => WithdrawalRequest::class,
                    'reference_id' => $lockedWithdrawal->id,
                    'description' => 'Withdrawal fee refund',
                    'created_by' => null,
                ]);
            }
        });
    }

    protected function walletChannelForCollection(PaymentCollection $collection): string
    {
        return $collection->method === 'card' ? self::WALLET_CARD : self::WALLET_MOBILE_MONEY;
    }

    protected function walletChannelForOrder(Order $order): string
    {
        if ($order->payment_method === 'card') {
            return self::WALLET_CARD;
        }

        $collection = PaymentCollection::query()
            ->where('payable_type', Order::class)
            ->where('payable_id', $order->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        return $collection ? $this->walletChannelForCollection($collection) : self::WALLET_MOBILE_MONEY;
    }

    /** @deprecated Use processWithdrawal() */
    public function requestWithdrawal(
        Business $business,
        float $amount,
        string $phoneNumber,
        string $pin,
        ?string $notes = null
    ): WithdrawalRequest {
        return $this->processWithdrawal($business, $amount, $phoneNumber, $pin, $notes);
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
