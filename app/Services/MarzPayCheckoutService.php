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

        if ($amount < 500) {
            return null;
        }

        $method = $this->marzPay->mapPaymentMethod($paymentMethod);

        return $this->marzPay->createAndInitiate(
            $payable,
            $amount,
            $method,
            $this->resolver->phoneFor($payable),
            $this->resolver->descriptionFor($payable),
        );
    }
}
