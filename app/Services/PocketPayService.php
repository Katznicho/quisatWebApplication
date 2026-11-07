<?php

namespace App\Services;

use App\Exceptions\PocketPayException;
use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenFloatTopup;
use App\Models\CanteenFloatTransaction;
use App\Models\CanteenSale;
use App\Models\ParentGuardian;
use App\Models\PocketPayCard;
use App\Models\PocketPayGatewaySetting;
use App\Models\PocketPayCardTransaction;
use App\Models\PocketPayTopup;
use App\Models\PocketPayWithdrawal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Quisat Pocket Pay: student canteen cards, canteen floats, sales, reversals and top-ups.
 *
 * Money rules:
 * - A float canteen: a sale debits the student card and the float. It is refused when the float is short.
 * - A collect canteen: a sale debits the card and adds the same amount to the canteen balance.
 *   The assigned person withdraws that balance. A sale is refused when the daily limit would be exceeded.
 * - A reversal is requested by an attendant and only moves money once an admin approves it.
 * - Card and float top-ups are mobile money (MarzPay). They are credited here, not to the
 *   school's business wallet.
 *
 * Rows are always locked card first, then canteen, to avoid deadlocks.
 */
class PocketPayService
{
    public const FEATURE_NAME = 'Quisat Pocket Pay';

    public const MAX_PIN_ATTEMPTS = 5;

    public const PIN_LOCK_MINUTES = 30;

    public const MIN_TOPUP = 500;

    public const MAX_TOPUP = 5000000;

    /** Cash float top-ups recorded by Quisat can be larger than a mobile money payment. */
    public const MAX_FLOAT_TOPUP = 50000000;

    public function __construct(
        protected MarzSmsService $sms,
        protected MarzPayService $marzPay,
    ) {}

    public function isEnabledFor(?Business $business): bool
    {
        return $business !== null && $business->hasFeatureByName(self::FEATURE_NAME);
    }

    public function findCard(Business $business, string $cardNumber): ?PocketPayCard
    {
        return PocketPayCard::query()
            ->where('business_id', $business->id)
            ->where('card_number', PocketPayCard::normalizeNumber($cardNumber))
            ->with('student.classRoom', 'student.parentGuardian')
            ->first();
    }

    // ------------------------------------------------------------------
    // Card PIN & lifecycle
    // ------------------------------------------------------------------

    public function activateCard(PocketPayCard $card, string $pin, Model $actor): PocketPayCard
    {
        $this->assertPinFormat($pin);

        if ($card->status === PocketPayCard::STATUS_BLOCKED) {
            throw new PocketPayException('This card is deactivated. Contact the school.', 423, 'card_blocked');
        }

        if ($card->isActive()) {
            throw new PocketPayException('This card is already active.', 422, 'card_already_active');
        }

        $card->forceFill([
            'pin' => Hash::make($pin),
            'status' => PocketPayCard::STATUS_ACTIVE,
            'failed_pin_attempts' => 0,
            'locked_until' => null,
            'activated_at' => now(),
            'activated_by_type' => $actor::class,
            'activated_by_id' => $actor->getKey(),
        ])->save();

        $this->notifyParent(
            $card,
            "{$this->studentName($card)}'s Quisat Pocket Pay card {$card->maskedNumber()} has been activated by {$this->actorLabel($actor)}.",
            'pocket_pay.card_activated',
        );

        return $card;
    }

    public function resetCardPin(PocketPayCard $card, string $pin, Model $actor): PocketPayCard
    {
        $this->assertPinFormat($pin);

        if (! $card->isActive()) {
            throw new PocketPayException('Only an active card can have its PIN reset. Activate it first.', 422, 'card_not_active');
        }

        $card->forceFill([
            'pin' => Hash::make($pin),
            'failed_pin_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->notifyParent(
            $card,
            "The PIN for {$this->studentName($card)}'s Pocket Pay card {$card->maskedNumber()} was reset by {$this->actorLabel($actor)}. If you did not expect this, contact the school.",
            'pocket_pay.pin_reset',
        );

        return $card;
    }

    /**
     * Checks the card PIN, counting failures and locking the card after too many.
     */
    public function verifyCardPin(PocketPayCard $card, string $pin): void
    {
        if ($card->isLocked()) {
            throw new PocketPayException(
                'Too many wrong PINs. The card is locked until '.$card->locked_until->format('H:i').', or ask a parent or attendant to reset the PIN.',
                423,
                'card_locked'
            );
        }

        if ($card->pin && Hash::check($pin, $card->pin)) {
            if ($card->failed_pin_attempts > 0) {
                $card->forceFill(['failed_pin_attempts' => 0, 'locked_until' => null])->save();
            }

            return;
        }

        $attempts = $card->failed_pin_attempts + 1;
        $locked = $attempts >= self::MAX_PIN_ATTEMPTS;

        $card->forceFill([
            'failed_pin_attempts' => $locked ? 0 : $attempts,
            'locked_until' => $locked ? now()->addMinutes(self::PIN_LOCK_MINUTES) : null,
        ])->save();

        if ($locked) {
            $this->notifyParent(
                $card,
                "{$this->studentName($card)}'s Pocket Pay card was locked after too many wrong PIN attempts.",
                'pocket_pay.card_locked',
            );

            throw new PocketPayException('Too many wrong PINs. The card is now locked.', 423, 'card_locked');
        }

        $left = self::MAX_PIN_ATTEMPTS - $attempts;

        throw new PocketPayException("Wrong PIN. {$left} attempt(s) left.", 422, 'wrong_pin');
    }

    /**
     * Clear a PIN lock early so the student can buy again with their current PIN.
     */
    public function unlockCard(PocketPayCard $card, Model $actor): PocketPayCard
    {
        $card->forceFill(['failed_pin_attempts' => 0, 'locked_until' => null])->save();

        $this->notifyParent(
            $card,
            "{$this->studentName($card)}'s Pocket Pay card {$card->maskedNumber()} was unlocked by {$this->actorLabel($actor)}.",
            'pocket_pay.card_unlocked',
        );

        return $card;
    }

    public function setDailyLimit(PocketPayCard $card, ?float $limit, Model $actor): PocketPayCard
    {
        if ($limit !== null && $limit < 0) {
            throw new PocketPayException('The daily limit cannot be negative.');
        }

        $card->forceFill(['daily_limit' => $limit])->save();

        $message = $limit === null
            ? "The daily spending limit on {$this->studentName($card)}'s Pocket Pay card was removed by {$this->actorLabel($actor)}."
            : "{$this->studentName($card)}'s Pocket Pay daily spending limit is now UGX ".$this->money($limit)." (set by {$this->actorLabel($actor)}).";

        $this->notifyParent($card, $message, 'pocket_pay.limit_changed');

        return $card;
    }

    public function setBlocked(PocketPayCard $card, bool $blocked, Model $actor): PocketPayCard
    {
        $card->forceFill([
            'status' => $blocked
                ? PocketPayCard::STATUS_BLOCKED
                : ($card->pin ? PocketPayCard::STATUS_ACTIVE : PocketPayCard::STATUS_INACTIVE),
        ])->save();

        $this->notifyParent(
            $card,
            "{$this->studentName($card)}'s Pocket Pay card {$card->maskedNumber()} was ".($blocked ? 'deactivated' : 'activated')." by {$this->actorLabel($actor)}.",
            $blocked ? 'pocket_pay.card_blocked' : 'pocket_pay.card_unblocked',
        );

        return $card;
    }

    public function spentToday(PocketPayCard $card): float
    {
        [$start, $end] = $this->todayBounds($card->business);

        return (float) CanteenSale::query()
            ->where('pocket_pay_card_id', $card->id)
            ->where('status', CanteenSale::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    public function remainingToday(PocketPayCard $card): ?float
    {
        if ($card->daily_limit === null) {
            return null;
        }

        return max(0, (float) $card->daily_limit - $this->spentToday($card));
    }

    // ------------------------------------------------------------------
    // Sales & reversals
    // ------------------------------------------------------------------

    public function makeSale(
        CanteenAttendant $attendant,
        string $cardNumber,
        float $amount,
        string $pin,
        ?string $description = null,
    ): CanteenSale {
        if ($amount <= 0) {
            throw new PocketPayException('Enter an amount greater than zero.');
        }

        $canteen = $attendant->canteen;
        $card = $this->findCard($attendant->business, $cardNumber);

        if (! $card) {
            throw new PocketPayException('Card not found at this school.', 404, 'card_not_found');
        }

        $this->assertCardUsable($card);
        $this->verifyCardPin($card, $pin);

        $sale = DB::transaction(function () use ($attendant, $canteen, $card, $amount, $description) {
            $card = PocketPayCard::query()->lockForUpdate()->findOrFail($card->id);
            $canteen = Canteen::query()->lockForUpdate()->findOrFail($canteen->id);

            $this->assertCardUsable($card);

            if (! $canteen->isActive()) {
                throw new PocketPayException('This canteen is not active.', 423, 'canteen_inactive');
            }

            if ((float) $card->balance < $amount) {
                throw new PocketPayException(
                    'Insufficient card balance. Available: UGX '.$this->money($card->balance).'.',
                    422,
                    'insufficient_card_balance'
                );
            }

            $remaining = $this->remainingToday($card);
            if ($remaining !== null && $amount > $remaining) {
                throw new PocketPayException(
                    'Daily limit reached. The student can spend UGX '.$this->money($remaining).' more today.',
                    422,
                    'daily_limit_exceeded'
                );
            }

            if ($canteen->usesFloat() && (float) $canteen->float_balance < $amount) {
                throw new PocketPayException(
                    'Canteen float is too low for this sale. Ask Quisat to top up the float.',
                    422,
                    'insufficient_float'
                );
            }

            $sale = CanteenSale::create([
                'business_id' => $card->business_id,
                'canteen_id' => $canteen->id,
                'canteen_attendant_id' => $attendant->id,
                'pocket_pay_card_id' => $card->id,
                'student_id' => $card->student_id,
                'amount' => $amount,
                'description' => $description,
                'status' => CanteenSale::STATUS_COMPLETED,
            ]);

            $this->moveCard($card, 'sale', 'debit', $amount, $sale, "Purchase at {$canteen->name}", $canteen->id);
            if ($canteen->collectsSales()) {
                $this->moveFloat($canteen, 'sale', 'credit', $amount, $sale, "Sale {$sale->receipt_number}");
            } else {
                $this->moveFloat($canteen, 'sale', 'debit', $amount, $sale, "Sale {$sale->receipt_number}");
            }

            return $sale;
        });

        $card->refresh();
        $this->notifyParent(
            $card,
            "{$this->studentName($card)} spent UGX {$this->money($amount)} at {$canteen->name}. Card balance: UGX {$this->money($card->balance)}. Ref {$sale->receipt_number}.",
            'pocket_pay.sale',
            $sale,
        );

        return $sale->load('card.student');
    }

    public function requestReversal(CanteenAttendant $attendant, CanteenSale $sale, string $reason): CanteenSale
    {
        if ($sale->canteen_id !== $attendant->canteen_id) {
            throw new PocketPayException('Sale not found.', 404, 'sale_not_found');
        }

        if (! $sale->canRequestReversal()) {
            throw new PocketPayException(
                $sale->hasPendingReversal() ? 'A reversal is already awaiting admin approval.' : 'This sale cannot be reversed.',
                422,
                'reversal_not_allowed'
            );
        }

        $sale->update([
            'reversal_status' => CanteenSale::REVERSAL_PENDING,
            'reversal_reason' => $reason,
            'reversal_requested_at' => now(),
        ]);

        return $sale;
    }

    public function approveReversal(CanteenSale $sale, User $admin, ?string $note = null): CanteenSale
    {
        $sale = DB::transaction(function () use ($sale, $admin, $note) {
            $card = PocketPayCard::query()->lockForUpdate()->findOrFail($sale->pocket_pay_card_id);
            $canteen = Canteen::query()->lockForUpdate()->findOrFail($sale->canteen_id);
            $sale = CanteenSale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->hasPendingReversal()) {
                throw new PocketPayException('This reversal has already been handled.', 422, 'reversal_not_pending');
            }

            $sale->update([
                'status' => CanteenSale::STATUS_REVERSED,
                'reversal_status' => CanteenSale::REVERSAL_APPROVED,
                'reversal_reviewed_by' => $admin->id,
                'reversal_reviewed_at' => now(),
                'reversal_review_note' => $note,
            ]);

            $amount = (float) $sale->amount;
            if ($canteen->collectsSales() && (float) $canteen->float_balance < $amount) {
                throw new PocketPayException(
                    'This sale cannot be reversed because the canteen has already withdrawn the money.',
                    422,
                    'balance_already_withdrawn'
                );
            }

            $this->moveCard($card, 'reversal', 'credit', $amount, $sale, "Refund of {$sale->receipt_number}", $canteen->id);
            if ($canteen->collectsSales()) {
                $this->moveFloat($canteen, 'reversal', 'debit', $amount, $sale, "Reversal of {$sale->receipt_number}");
            } else {
                $this->moveFloat($canteen, 'reversal', 'credit', $amount, $sale, "Reversal of {$sale->receipt_number}");
            }

            return $sale;
        });

        $card = $sale->card()->first();
        $this->notifyParent(
            $card,
            "UGX {$this->money($sale->amount)} for sale {$sale->receipt_number} was refunded to {$this->studentName($card)}'s card. Balance: UGX {$this->money($card->balance)}.",
            'pocket_pay.reversal',
            $sale,
        );

        return $sale;
    }

    public function rejectReversal(CanteenSale $sale, User $admin, ?string $note = null): CanteenSale
    {
        return DB::transaction(function () use ($sale, $admin, $note) {
            $sale = CanteenSale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->hasPendingReversal()) {
                throw new PocketPayException('This reversal has already been handled.', 422, 'reversal_not_pending');
            }

            $sale->update([
                'reversal_status' => CanteenSale::REVERSAL_REJECTED,
                'reversal_reviewed_by' => $admin->id,
                'reversal_reviewed_at' => now(),
                'reversal_review_note' => $note,
            ]);

            return $sale;
        });
    }

    // ------------------------------------------------------------------
    // Top-ups (mobile money via MarzPay)
    // ------------------------------------------------------------------

    /**
     * @param  PocketPayCard|Canteen  $target
     * @return array{topup:PocketPayTopup,payment:array}
     */
    public function initiateTopup(Model $target, float $amount, string $phone, Model $initiator): array
    {
        if (! $target instanceof PocketPayCard && ! $target instanceof Canteen) {
            throw new \InvalidArgumentException('Top-ups are only for cards and canteens.');
        }

        if ($amount < self::MIN_TOPUP || $amount > self::MAX_TOPUP) {
            throw new PocketPayException(
                'Top-up must be between UGX '.$this->money(self::MIN_TOPUP).' and UGX '.$this->money(self::MAX_TOPUP).'.'
            );
        }

        if ($target instanceof PocketPayCard && $target->status === PocketPayCard::STATUS_BLOCKED) {
            throw new PocketPayException('This card is deactivated. Contact the school.', 423, 'card_blocked');
        }

        $normalized = $this->sms->normalizePhone($phone);

        if (! $normalized) {
            throw new PocketPayException('Enter a valid mobile money number.', 422, 'invalid_phone');
        }

        $gateway = PocketPayGatewaySetting::current();

        if (! $gateway->hasPayCredentials()) {
            throw new PocketPayException('Pocket Pay mobile money is not set up yet.', 422, 'gateway_not_configured');
        }

        $topup = PocketPayTopup::create([
            'business_id' => $target->business_id,
            'topupable_type' => $target::class,
            'topupable_id' => $target->id,
            'amount' => $amount,
            'phone_number' => $normalized,
            'status' => 'pending',
            'initiated_by_type' => $initiator::class,
            'initiated_by_id' => $initiator->getKey(),
        ]);

        $payment = $this->marzPay->forPocketPay()->createAndInitiate(
            $topup,
            (int) round($amount),
            'mobile_money',
            $normalized,
            $topup->marzPayDescription(),
        );

        if (! $payment['success']) {
            $topup->update(['status' => 'failed']);

            throw new PocketPayException($payment['message'] ?: 'Mobile money prompt could not be sent.', 422, 'topup_failed');
        }

        $topup->update(['status' => 'processing']);

        return ['topup' => $topup->fresh(), 'payment' => $payment['data']];
    }

    /**
     * Send money out of the Pocket Pay Marz Pay account. Student cards and canteen floats are not reduced.
     */
    public function withdrawToPhone(User $actor, float $amount, string $phone, ?string $notes = null): PocketPayWithdrawal
    {
        $this->assertGatewayReady();
        $this->assertWithdrawalAmount($amount);
        $normalized = $this->sms->normalizePhone($phone);

        if (! $normalized) {
            throw new PocketPayException('Enter a valid mobile money number.', 422, 'invalid_phone');
        }

        $withdrawal = PocketPayWithdrawal::create([
            'amount' => $amount,
            'method' => PocketPayWithdrawal::METHOD_MOBILE,
            'phone_number' => $normalized,
            'status' => 'processing',
            'notes' => $notes,
            'requested_by' => $actor->id,
        ]);

        $result = $this->marzPay->forPocketPay()->sendMoney(
            $withdrawal->uuid,
            $amount,
            $normalized,
            'Quisat Pocket Pay withdrawal',
        );

        return $this->finishWithdrawal($withdrawal, $result);
    }

    /**
     * Pay out a canteen that keeps its sales. The sale balance is reduced, then the money is sent from Marz Pay.
     */
    public function withdrawCanteenBalance(User|CanteenAttendant $actor, Canteen $canteen, float $amount, string $phone, ?string $notes = null): PocketPayWithdrawal
    {
        if (! $canteen->collectsSales()) {
            throw new PocketPayException('This canteen is paid from a float. There is no sales balance to withdraw.', 422, 'not_collect_canteen');
        }

        $allowed = $actor instanceof CanteenAttendant
            ? $canteen->attendantCanWithdraw($actor)
            : $canteen->canBeWithdrawnBy($actor);

        if (! $allowed) {
            throw new PocketPayException('You are not allowed to withdraw this canteen’s balance.', 403, 'withdraw_forbidden');
        }

        $this->assertGatewayReady();
        $this->assertWithdrawalAmount($amount);
        $normalized = $this->sms->normalizePhone($phone);

        if (! $normalized) {
            throw new PocketPayException('Enter a valid mobile money number.', 422, 'invalid_phone');
        }

        $withdrawal = DB::transaction(function () use ($actor, $canteen, $amount, $normalized, $notes) {
            $canteen = Canteen::query()->lockForUpdate()->findOrFail($canteen->id);

            if ((float) $canteen->float_balance < $amount) {
                throw new PocketPayException(
                    'The canteen balance is UGX '.$this->money($canteen->float_balance).'.',
                    422,
                    'insufficient_canteen_balance'
                );
            }

            $withdrawal = PocketPayWithdrawal::create([
                'amount' => $amount,
                'method' => PocketPayWithdrawal::METHOD_MOBILE,
                'phone_number' => $normalized,
                'status' => 'processing',
                'notes' => $notes,
                'requested_by' => $actor instanceof User ? $actor->id : null,
                'canteen_id' => $canteen->id,
                'canteen_attendant_id' => $actor instanceof CanteenAttendant ? $actor->id : null,
            ]);

            $this->moveFloat($canteen, 'withdrawal', 'debit', $amount, $withdrawal, "Withdrawal to {$normalized}");

            return $withdrawal;
        });

        try {
            $result = $this->marzPay->forPocketPay()->sendMoney(
                $withdrawal->uuid,
                $amount,
                $normalized,
                'Quisat Pocket Pay canteen withdrawal',
            );

            return $this->finishWithdrawal($withdrawal, $result);
        } catch (PocketPayException $e) {
            $this->restoreCanteenWithdrawal($withdrawal);

            throw $e;
        }
    }

    public function withdrawToBank(
        User $actor,
        float $amount,
        string $bankName,
        string $accountNumber,
        ?string $accountName = null,
        ?string $bankBranch = null,
        ?string $notes = null,
    ): PocketPayWithdrawal {
        $this->assertGatewayReady();
        $this->assertWithdrawalAmount($amount);

        $withdrawal = PocketPayWithdrawal::create([
            'amount' => $amount,
            'method' => PocketPayWithdrawal::METHOD_BANK,
            'bank_name' => $bankName,
            'bank_account_number' => $accountNumber,
            'bank_account_name' => $accountName,
            'bank_branch' => $bankBranch,
            'status' => 'processing',
            'notes' => $notes,
            'requested_by' => $actor->id,
        ]);

        $result = $this->marzPay->forPocketPay()->createBankTransfer(
            $withdrawal->uuid,
            $amount,
            $bankName,
            $accountNumber,
            'main',
            $accountName,
            $bankBranch,
            'Quisat Pocket Pay withdrawal',
        );

        return $this->finishWithdrawal($withdrawal, $result);
    }

    protected function assertGatewayReady(): void
    {
        if (! PocketPayGatewaySetting::current()->hasPayCredentials()) {
            throw new PocketPayException('Pocket Pay Marz Pay keys are not set up yet.', 422, 'gateway_not_configured');
        }
    }

    protected function assertWithdrawalAmount(float $amount): void
    {
        if ($amount < self::MIN_TOPUP || $amount > self::MAX_TOPUP) {
            throw new PocketPayException(
                'Withdrawal must be between UGX '.$this->money(self::MIN_TOPUP).' and UGX '.$this->money(self::MAX_TOPUP).'.'
            );
        }
    }

    protected function finishWithdrawal(PocketPayWithdrawal $withdrawal, array $result): PocketPayWithdrawal
    {
        if (! ($result['success'] ?? false)) {
            $withdrawal->update([
                'status' => 'failed',
                'notes' => $result['message'] ?? $withdrawal->notes,
            ]);

            throw new PocketPayException($result['message'] ?: 'Withdrawal could not be sent.', 422, 'withdrawal_failed');
        }

        $status = (string) ($result['status'] ?? 'processing');
        $withdrawal->update([
            'status' => in_array($status, ['completed', 'sandbox'], true) ? 'completed' : 'processing',
            'marz_transaction_uuid' => $result['transaction_uuid'] ?? null,
            'provider_reference' => $result['provider_reference'] ?? null,
        ]);

        return $withdrawal->fresh();
    }

    protected function restoreCanteenWithdrawal(PocketPayWithdrawal $withdrawal): void
    {
        if (! $withdrawal->canteen_id) {
            return;
        }

        DB::transaction(function () use ($withdrawal) {
            $canteen = Canteen::query()->lockForUpdate()->find($withdrawal->canteen_id);

            if (! $canteen) {
                return;
            }

            $alreadyRestored = CanteenFloatTransaction::query()
                ->where('reference_type', $withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('type', 'wd_restore')
                ->exists();

            if ($alreadyRestored) {
                return;
            }

            $this->moveFloat($canteen, 'wd_restore', 'credit', (float) $withdrawal->amount, $withdrawal, 'Withdrawal could not be sent');
        });
    }

    /**
     * Credit a completed top-up exactly once (webhook and manual sync can both call this).
     */
    /**
     * Credit a canteen float with cash paid at the canteen. The proof of payment is already stored.
     */
    public function recordFloatTopup(
        Canteen $canteen,
        float $amount,
        string $proofPath,
        User $recordedBy,
        ?string $proofName = null,
        ?string $paidOn = null,
        ?string $reference = null,
        ?string $note = null,
    ): CanteenFloatTopup {
        if ($canteen->collectsSales()) {
            throw new PocketPayException('This canteen does not use a float. Each sale adds to its balance.', 422, 'float_not_used');
        }

        if ($amount < self::MIN_TOPUP || $amount > self::MAX_FLOAT_TOPUP) {
            throw new PocketPayException(
                'Top-up amount must be between UGX '.$this->money(self::MIN_TOPUP).' and UGX '.$this->money(self::MAX_FLOAT_TOPUP).'.',
                422,
                'invalid_amount',
            );
        }

        return DB::transaction(function () use ($canteen, $amount, $proofPath, $recordedBy, $proofName, $paidOn, $reference, $note) {
            $canteen = Canteen::query()->lockForUpdate()->findOrFail($canteen->id);
            $reference = filled($reference) ? trim($reference) : null;

            $topup = CanteenFloatTopup::create([
                'business_id' => $canteen->business_id,
                'canteen_id' => $canteen->id,
                'amount' => $amount,
                'paid_on' => $paidOn ?: now()->toDateString(),
                'payment_reference' => $reference,
                'proof_path' => $proofPath,
                'proof_name' => $proofName,
                'note' => filled($note) ? trim($note) : null,
                'recorded_by' => $recordedBy->id,
            ]);

            $this->moveFloat($canteen, 'topup', 'credit', $amount, $topup, 'Float top-up (cash at canteen)'.($reference ? " · Ref {$reference}" : ''));

            return $topup;
        });
    }

    public function creditTopup(PocketPayTopup $topup): void
    {
        $credited = DB::transaction(function () use ($topup) {
            $topup = PocketPayTopup::query()->lockForUpdate()->findOrFail($topup->id);

            if ($topup->credited_at !== null) {
                return null;
            }

            $amount = (float) $topup->amount;
            $target = $topup->topupable;

            if ($target instanceof PocketPayCard) {
                $card = PocketPayCard::query()->lockForUpdate()->findOrFail($target->id);
                $this->moveCard($card, 'topup', 'credit', $amount, $topup, 'Mobile money top-up');
            } elseif ($target instanceof Canteen) {
                $canteen = Canteen::query()->lockForUpdate()->findOrFail($target->id);
                $this->moveFloat($canteen, 'topup', 'credit', $amount, $topup, 'Float top-up (mobile money)');
            } else {
                return null;
            }

            $topup->update(['status' => 'completed', 'credited_at' => now()]);

            return $topup;
        });

        if ($credited && $credited->isForCard()) {
            $card = $credited->topupable()->first();
            $this->notifyParent(
                $card,
                "UGX {$this->money($credited->amount)} was added to {$this->studentName($card)}'s Pocket Pay card. Balance: UGX {$this->money($card->balance)}.",
                'pocket_pay.topup',
                $credited,
            );
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function assertPinFormat(string $pin): void
    {
        if (! preg_match('/^\d{4}$/', $pin)) {
            throw new PocketPayException('PIN must be exactly 4 digits.', 422, 'invalid_pin_format');
        }
    }

    protected function assertCardUsable(PocketPayCard $card): void
    {
        if ($card->status === PocketPayCard::STATUS_INACTIVE) {
            throw new PocketPayException('This card is not activated yet.', 422, 'card_not_active');
        }

        if ($card->status === PocketPayCard::STATUS_BLOCKED) {
            throw new PocketPayException('This card is deactivated. Contact the school.', 423, 'card_blocked');
        }
    }

    protected function moveCard(
        PocketPayCard $card,
        string $type,
        string $direction,
        float $amount,
        Model $reference,
        string $description,
        ?int $canteenId = null,
    ): void {
        $card->balance = (float) $card->balance + ($direction === 'credit' ? $amount : -$amount);
        $card->save();

        PocketPayCardTransaction::create([
            'pocket_pay_card_id' => $card->id,
            'business_id' => $card->business_id,
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'balance_after' => $card->balance,
            'canteen_id' => $canteenId,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'description' => $description,
        ]);
    }

    protected function moveFloat(
        Canteen $canteen,
        string $type,
        string $direction,
        float $amount,
        Model $reference,
        string $description,
    ): void {
        $canteen->float_balance = (float) $canteen->float_balance + ($direction === 'credit' ? $amount : -$amount);
        $canteen->save();

        CanteenFloatTransaction::create([
            'canteen_id' => $canteen->id,
            'business_id' => $canteen->business_id,
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'balance_after' => $canteen->float_balance,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'description' => $description,
        ]);
    }

    /**
     * Every card action is reported to the parent by SMS when they have a phone number.
     */
    protected function notifyParent(?PocketPayCard $card, string $message, string $purpose, ?Model $related = null): void
    {
        $phone = $card?->student?->parentGuardian?->phone;

        if (! $card || ! $phone) {
            return;
        }

        $this->sms->queue($phone, 'Quisat Pocket Pay: '.$message, $purpose, $card->business, $related ?? $card);
    }

    /**
     * @return array{0:Carbon,1:Carbon} today's bounds in the school's timezone, as app-time instants
     */
    protected function todayBounds(?Business $business): array
    {
        $tz = $business?->timezoneName() ?? config('app.timezone');
        $now = now()->setTimezone($tz);

        return [
            $now->copy()->startOfDay()->setTimezone(config('app.timezone')),
            $now->copy()->endOfDay()->setTimezone(config('app.timezone')),
        ];
    }

    protected function studentName(?PocketPayCard $card): string
    {
        return $card?->student?->first_name ?: 'Your child';
    }

    protected function actorLabel(Model $actor): string
    {
        return match (true) {
            $actor instanceof CanteenAttendant => 'attendant '.$actor->name.($actor->canteen ? ' ('.$actor->canteen->name.')' : ''),
            $actor instanceof ParentGuardian => 'a parent in the Quisat app',
            $actor instanceof User => $actor->isAdmin() ? 'Quisat' : 'the school admin',
            default => 'Quisat',
        };
    }

    protected function money(float|string|null $amount): string
    {
        return number_format((float) $amount);
    }
}
