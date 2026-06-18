<?php

namespace App\Models\Concerns;

use App\Models\PaymentCollection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait InteractsWithMarzPay
{
    public function paymentCollections(): MorphMany
    {
        return $this->morphMany(PaymentCollection::class, 'payable');
    }

    public function latestPaymentCollection(): ?PaymentCollection
    {
        return $this->paymentCollections()->latest()->first();
    }

    public function marzPayAmount(): int
    {
        return 0;
    }

    public function marzPayDescription(): string
    {
        return 'Quisat payment';
    }

    public function marzPayPhoneNumber(): ?string
    {
        return null;
    }

    public function markMarzPayCompleted(PaymentCollection $collection): void
    {
        //
    }

    public function markMarzPayFailed(PaymentCollection $collection): void
    {
        //
    }
}
