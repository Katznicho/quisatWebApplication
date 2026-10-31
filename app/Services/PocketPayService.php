<?php

namespace App\Services;

use App\Exceptions\PocketPayException;
use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenFloatTransaction;
use App\Models\CanteenSale;
use App\Models\ParentGuardian;
use App\Models\PocketPayCard;
use App\Models\PocketPayCardTransaction;
use App\Models\PocketPayTopup;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Quisat Pocket Pay: student canteen cards, canteen floats, sales, reversals and top-ups.
 *
 * Money rules:
 * - A sale debits the student card AND the canteen float. It is refused if either is short
 *   or the card's daily limit would be exceeded.
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
            throw new PocketPayException('This card is blocked. Contact the school.', 423, 'card_blocked');
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
            "{$this->studentName($card)}'s Pocket Pay card {$card->maskedNumber()} was ".($blocked ? 'blocked' : 'unblocked')." by {$this->actorLabel($actor)}.",
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

            if ((float) $canteen->float_balance < $amount) {
                throw new PocketPayException(
                    'Canteen float is too low for this sale. Ask the school admin to top up the float.',
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
            $this->moveFloat($canteen, 'sale', 'debit', $amount, $sale, "Sale {$sale->receipt_number}");

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
            $this->moveCard($card, 'reversal', 'credit', $amount, $sale, "Refund of {$sale->receipt_number}", $canteen->id);
            $this->moveFloat($canteen, 'reversal', 'credit', $amount, $sale, "Reversal of {$sale->receipt_number}");

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
            throw new PocketPayException('This card is blocked. Contact the school.', 423, 'card_blocked');
        }

        $normalized = $this->sms->normalizePhone($phone);

        if (! $normalized) {
            throw new PocketPayException('Enter a valid mobile money number.', 422, 'invalid_phone');
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

        $payment = $this->marzPay->createAndInitiate(
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
     * Credit a completed top-up exactly once (webhook and manual sync can both call this).
     */
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
            throw new PocketPayException('This card is blocked. Contact the school.', 423, 'card_blocked');
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
            $actor instanceof User => 'the school admin',
            default => 'Quisat',
        };
    }

    protected function money(float|string|null $amount): string
    {
        return number_format((float) $amount);
    }
}
