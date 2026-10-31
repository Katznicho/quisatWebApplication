<?php

namespace App\Services;

use App\Exceptions\PocketPayException;
use App\Models\CanteenAttendant;
use Illuminate\Support\Facades\Hash;

/**
 * Quisat Attendant login: phone + 4-digit PIN. A one-time PIN (OTP) sent by SMS is used both
 * for first-time account setup and for "forgot PIN".
 */
class CanteenAttendantAuthService
{
    public const OTP_TTL_MINUTES = 10;

    public const OTP_RESEND_SECONDS = 60;

    public const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        protected MarzSmsService $sms,
        protected PocketPayService $pocketPay,
    ) {}

    public function findByPhone(string $phone): ?CanteenAttendant
    {
        $normalized = $this->sms->normalizePhone($phone);

        return $normalized ? CanteenAttendant::query()->where('phone', $normalized)->first() : null;
    }

    /**
     * Send a one-time PIN. Returns false when one was sent too recently.
     */
    public function sendOtp(CanteenAttendant $attendant): bool
    {
        $lastSentAt = $attendant->otp_expires_at?->copy()->subMinutes(self::OTP_TTL_MINUTES);

        if ($lastSentAt && $lastSentAt->diffInSeconds(now()) < self::OTP_RESEND_SECONDS) {
            return false;
        }

        $otp = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $attendant->forceFill([
            'otp_hash' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'otp_attempts' => 0,
        ])->save();

        $intro = $attendant->status === CanteenAttendant::STATUS_PENDING_SETUP
            ? "Welcome to Quisat Attendant, {$attendant->name}. "
            : '';

        $this->sms->sendNow(
            $attendant->phone,
            "{$intro}Your one-time PIN is {$otp}. It expires in ".self::OTP_TTL_MINUTES.' minutes. Do not share it.',
            'pocket_pay.attendant_otp',
            $attendant->business,
            $attendant,
        );

        return true;
    }

    /**
     * Check the OTP and set the attendant's new 4-digit PIN. Activates a pending account.
     */
    public function completeWithOtp(string $phone, string $otp, string $pin): CanteenAttendant
    {
        $this->pocketPay->assertPinFormat($pin);

        $attendant = $this->findByPhone($phone);

        if (! $attendant || $attendant->status === CanteenAttendant::STATUS_DISABLED) {
            throw new PocketPayException('Invalid or expired one-time PIN.', 422, 'invalid_otp');
        }

        if (! $attendant->otp_hash || ! $attendant->otp_expires_at || $attendant->otp_expires_at->isPast()) {
            throw new PocketPayException('Invalid or expired one-time PIN. Request a new one.', 422, 'invalid_otp');
        }

        if (! Hash::check($otp, $attendant->otp_hash)) {
            $attempts = $attendant->otp_attempts + 1;
            $attendant->forceFill(
                $attempts >= self::MAX_OTP_ATTEMPTS
                    ? ['otp_hash' => null, 'otp_expires_at' => null, 'otp_attempts' => 0]
                    : ['otp_attempts' => $attempts]
            )->save();

            throw new PocketPayException('Invalid or expired one-time PIN.', 422, 'invalid_otp');
        }

        $attendant->forceFill([
            'pin' => Hash::make($pin),
            'status' => CanteenAttendant::STATUS_ACTIVE,
            'otp_hash' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'failed_pin_attempts' => 0,
            'locked_until' => null,
        ])->save();

        // A PIN reset signs out every other device.
        $attendant->tokens()->delete();

        return $attendant;
    }

    public function login(string $phone, string $pin): CanteenAttendant
    {
        $attendant = $this->findByPhone($phone);

        if (! $attendant || ! $attendant->pin) {
            throw new PocketPayException(
                $attendant && $attendant->status === CanteenAttendant::STATUS_PENDING_SETUP
                    ? 'Set up your account with the one-time PIN sent to your phone.'
                    : 'Wrong phone number or PIN.',
                422,
                $attendant ? 'setup_required' : 'invalid_credentials'
            );
        }

        if ($attendant->status === CanteenAttendant::STATUS_DISABLED) {
            throw new PocketPayException('Your account is disabled. Contact the school admin.', 403, 'account_disabled');
        }

        if ($attendant->isLocked()) {
            throw new PocketPayException(
                'Too many wrong PINs. Try again after '.$attendant->locked_until->format('H:i').' or use Forgot PIN.',
                423,
                'account_locked'
            );
        }

        if (! Hash::check($pin, $attendant->pin)) {
            $attempts = $attendant->failed_pin_attempts + 1;
            $locked = $attempts >= PocketPayService::MAX_PIN_ATTEMPTS;
            $attendant->forceFill([
                'failed_pin_attempts' => $locked ? 0 : $attempts,
                'locked_until' => $locked ? now()->addMinutes(PocketPayService::PIN_LOCK_MINUTES) : null,
            ])->save();

            throw new PocketPayException('Wrong phone number or PIN.', 422, 'invalid_credentials');
        }

        $this->assertCanWork($attendant);

        $attendant->forceFill([
            'failed_pin_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        return $attendant;
    }

    public function changePin(CanteenAttendant $attendant, string $currentPin, string $newPin): void
    {
        $this->pocketPay->assertPinFormat($newPin);

        if (! $attendant->pin || ! Hash::check($currentPin, $attendant->pin)) {
            throw new PocketPayException('Current PIN is wrong.', 422, 'wrong_pin');
        }

        if ($currentPin === $newPin) {
            throw new PocketPayException('Choose a PIN different from the current one.');
        }

        $attendant->forceFill(['pin' => Hash::make($newPin)])->save();
    }

    /**
     * The attendant, their canteen and their school must all be able to trade.
     */
    public function assertCanWork(CanteenAttendant $attendant): void
    {
        if ($attendant->status !== CanteenAttendant::STATUS_ACTIVE) {
            throw new PocketPayException('Your account is not active.', 403, 'account_inactive');
        }

        if (! $attendant->canteen || ! $attendant->canteen->isActive()) {
            throw new PocketPayException('Your canteen is not active. Contact the school admin.', 403, 'canteen_inactive');
        }

        if (! $this->pocketPay->isEnabledFor($attendant->business)) {
            throw new PocketPayException('Quisat Pocket Pay is not enabled for your school.', 403, 'feature_disabled');
        }
    }
}
