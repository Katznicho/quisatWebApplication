<?php

namespace App\Services;

use App\Models\EventAttendee;
use App\Models\KidsEventRegistration;
use App\Models\Order;
use App\Models\ParentCornerRegistration;
use App\Models\PaymentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarzPayPayableResolver
{
    public function resolve(string $type, string $identifier): ?Model
    {
        return match ($type) {
            'order' => Order::query()
                ->where('uuid', $identifier)
                ->orWhere('id', $identifier)
                ->first(),
            'kids_event_registration' => KidsEventRegistration::query()
                ->where('uuid', $identifier)
                ->orWhere('id', $identifier)
                ->first(),
            'parent_corner_registration' => ParentCornerRegistration::query()
                ->where('uuid', $identifier)
                ->orWhere('id', $identifier)
                ->first(),
            'program_registration', 'event_attendee' => EventAttendee::query()
                ->where('uuid', $identifier)
                ->orWhere('id', $identifier)
                ->first(),
            default => null,
        };
    }

    public function payableTypeKey(Model $payable): string
    {
        return match ($payable::class) {
            Order::class => 'order',
            KidsEventRegistration::class => 'kids_event_registration',
            ParentCornerRegistration::class => 'parent_corner_registration',
            EventAttendee::class => 'program_registration',
            default => Str::snake(class_basename($payable)),
        };
    }

    public function amountFor(Model $payable): int
    {
        if (method_exists($payable, 'marzPayAmount')) {
            return max(0, (int) $payable->marzPayAmount());
        }

        return 0;
    }

    public function phoneFor(Model $payable): ?string
    {
        if (method_exists($payable, 'marzPayPhoneNumber')) {
            return $payable->marzPayPhoneNumber();
        }

        return null;
    }

    public function descriptionFor(Model $payable): string
    {
        if (method_exists($payable, 'marzPayDescription')) {
            return $payable->marzPayDescription();
        }

        return 'Quisat payment';
    }

    public function applyCallback(PaymentCollection $collection): void
    {
        $payable = $collection->payable;

        if (! $payable) {
            return;
        }

        if ($collection->status === 'completed' && method_exists($payable, 'markMarzPayCompleted')) {
            $payable->markMarzPayCompleted($collection);
        }

        if ($collection->status === 'failed' && method_exists($payable, 'markMarzPayFailed')) {
            $payable->markMarzPayFailed($collection);
        }
    }
}
