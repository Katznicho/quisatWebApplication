<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\ParentGuardian;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;

class FeeParentNotificationService
{
    use ResolvesPushDeviceTokens;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notifyCreated(Fee $fee): void
    {
        $fee->loadMissing(['student.parentGuardian', 'clinicPatient.parentGuardian', 'term', 'business']);
        $parent = $fee->parentGuardian();

        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $name = $fee->billableName();
        $currency = $fee->business?->displayCurrency() ?? 'UGX';
        $amount = number_format((float) $fee->balance, 0);
        $type = ucfirst((string) $fee->fee_type);
        $isClinic = $fee->isClinicFee();
        $title = $isClinic ? 'Clinic fee pending' : 'School fee pending';
        $body = "{$type} of {$currency} {$amount} is due for {$name}. Open Fees to pay.";

        $this->notify($parent, $title, $body, $fee, $isClinic ? 'clinic_fee_created' : 'school_fee_created');
    }

    public function notifyPaid(Fee $fee): void
    {
        $fee->loadMissing(['student.parentGuardian', 'clinicPatient.parentGuardian', 'term', 'business']);
        $parent = $fee->parentGuardian();

        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $name = $fee->billableName();
        $receipt = $fee->receipt_number ? " Receipt {$fee->receipt_number}." : '';
        $isClinic = $fee->isClinicFee();
        $label = $isClinic ? 'Clinic fee' : 'School fee';
        $title = $fee->remainingBalance() > 0 ? "{$label} payment received" : "{$label} paid";
        $currency = $fee->business?->displayCurrency() ?? 'UGX';
        $body = $fee->remainingBalance() > 0
            ? $currency.' '.number_format((float) $fee->amount_paid, 0)." received for {$name}. Balance {$currency} ".number_format((float) $fee->remainingBalance(), 0).".{$receipt}"
            : "Payment received for {$name}.{$receipt} Your receipt is available in Fees.";

        $this->notify($parent, $title, $body, $fee, $isClinic ? 'clinic_fee_paid' : 'school_fee_paid');
    }

    public function notifyOverdue(Fee $fee): void
    {
        $fee->loadMissing(['student.parentGuardian', 'clinicPatient.parentGuardian']);
        $parent = $fee->parentGuardian();

        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $name = $fee->billableName();
        $amount = number_format((float) $fee->remainingBalance(), 0);
        $isClinic = $fee->isClinicFee();
        $this->notify(
            $parent,
            $isClinic ? 'Clinic fee overdue' : 'School fee overdue',
            "A fee for {$name} is overdue. Balance due: ".($fee->business?->displayCurrency() ?? 'UGX')." {$amount}.",
            $fee,
            $isClinic ? 'clinic_fee_overdue' : 'school_fee_overdue'
        );
    }

    protected function notify(ParentGuardian $parent, string $title, string $body, Fee $fee, string $type): void
    {
        $data = [
            'type' => $type,
            'screen' => 'Fees',
            'fee_id' => (string) $fee->id,
            'fee_uuid' => (string) $fee->uuid,
            'billing_context' => $fee->billingContext(),
            'student_id' => $fee->student_id ? (string) $fee->student_id : null,
            'clinic_patient_id' => $fee->clinic_patient_id ? (string) $fee->clinic_patient_id : null,
            'title' => $title,
        ];

        UserNotification::create([
            'notifiable_type' => $parent::class,
            'notifiable_id' => $parent->getKey(),
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $tokens = $this->resolveDeviceTokens(collect([$parent]));

        if ($tokens->isEmpty()) {
            return;
        }

        $this->pushService->sendExpoBatch($tokens, $title, $body, $data);
    }
}
