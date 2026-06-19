<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class MarzPayCheckoutService
{
    public function __construct(
        protected MarzPayService $marzPay,
        protected MarzPayPayableResolver $resolver,
    ) {
    }

    public function maybeInitiate(Model $payable, ?string $paymentMethod): ?array
    {
        if (! $paymentMethod || ! $this->marzPay->isOnlinePaymentMethod($paymentMethod)) {
            return null;
        }

        $amount = $this->resolver->amountFor($payable);

        if ($amount < 1) {
            return null;
        }

        $method = $this->marzPay->mapPaymentMethod($paymentMethod);
        $charge = \App\Models\MarzPaySetting::current()->calculateCharge($amount, $method);

        if ($charge['total_amount'] < 500) {
            return null;
        }

        return $this->marzPay->createAndInitiate(
            $payable,
            $amount,
            $method,
            $this->resolver->phoneFor($payable),
            $this->resolver->descriptionFor($payable),
        );
    }

    /**
     * @return array{
     *     payment_initiated: bool,
     *     payment_error: ?string,
     *     payment: ?array,
     *     message: string,
     * }
     */
    public function registrationPaymentMeta(
        ?array $paymentResult,
        string $paymentMethod,
        string $onlineSuccessMessage = 'Registration saved. Complete payment to confirm.',
        string $cashSuccessMessage = 'Registered successfully!',
    ): array {
        $isOnline = $this->marzPay->isOnlinePaymentMethod($paymentMethod);
        $paymentInitiated = $isOnline && $paymentResult && ($paymentResult['success'] ?? false);
        $paymentError = $isOnline && ! $paymentInitiated
            ? (data_get($paymentResult, 'message') ?: 'Please try again, choose cash, or ensure the amount is at least 500 UGX.')
            : null;

        if ($paymentInitiated) {
            $message = $onlineSuccessMessage;
        } elseif ($isOnline) {
            $message = 'Registration saved, but online payment could not be started.';
        } else {
            $message = $cashSuccessMessage;
        }

        return [
            'payment_initiated' => $paymentInitiated,
            'payment_error' => $paymentError,
            'payment' => $paymentInitiated ? ($paymentResult['data'] ?? null) : null,
            'message' => $message,
        ];
    }
}
