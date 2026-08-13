<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\ParentGuardian;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use Illuminate\Support\Collection;

class FeeParentNotificationService
{
    use ResolvesPushDeviceTokens;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notifyCreated(Fee $fee): void
    {
        $fee->loadMissing(['student.parentGuardian', 'term', 'business']);
        $parent = $fee->student?->parentGuardian;

        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $studentName = $fee->student?->full_name ?? 'your child';
        $amount = number_format((float) $fee->balance, 0);
        $type = ucfirst((string) $fee->fee_type);
        $title = 'School fee pending';
        $body = "{$type} of UGX {$amount} is due for {$studentName}. Open Fees to pay.";

        $this->notify($parent, $title, $body, $fee, 'school_fee_created');
    }

    public function notifyPaid(Fee $fee): void
    {
        $fee->loadMissing(['student.parentGuardian', 'term', 'business']);
        $parent = $fee->student?->parentGuardian;

        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $studentName = $fee->student?->full_name ?? 'your child';
        $receipt = $fee->receipt_number ? " Receipt {$fee->receipt_number}." : '';
        $title = $fee->remainingBalance() > 0 ? 'School fee payment received' : 'School fee paid';
        $body = $fee->remainingBalance() > 0
            ? "UGX ".number_format((float) $fee->amount_paid, 0)." received for {$studentName}. Balance UGX ".number_format((float) $fee->remainingBalance(), 0).".{$receipt}"
            : "Payment received for {$studentName}.{$receipt} Your receipt is available in Fees.";

        $this->notify($parent, $title, $body, $fee, 'school_fee_paid');
    }

    public function notifyOverdue(Fee $fee): void
    {
        $fee->loadMissing(['student.parentGuardian']);
        $parent = $fee->student?->parentGuardian;

        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $studentName = $fee->student?->full_name ?? 'your child';
        $amount = number_format((float) $fee->remainingBalance(), 0);
        $this->notify(
            $parent,
            'School fee overdue',
            "A fee for {$studentName} is overdue. Balance due: UGX {$amount}.",
            $fee,
            'school_fee_overdue'
        );
    }

    protected function notify(ParentGuardian $parent, string $title, string $body, Fee $fee, string $type): void
    {
        $data = [
            'type' => $type,
            'screen' => 'Fees',
            'fee_id' => (string) $fee->id,
            'fee_uuid' => (string) $fee->uuid,
            'student_id' => (string) $fee->student_id,
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
