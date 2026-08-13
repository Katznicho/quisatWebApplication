<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
use App\Services\FeeInvoiceService;
use App\Services\FeeParentNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Fee extends Model
{
    use HasFactory, InteractsWithMarzPay, SoftDeletes;

    protected $fillable = [
        'business_id',
        'student_id',
        'term_id',
        'fee_type',
        'amount',
        'amount_paid',
        'balance',
        'due_date',
        'payment_status',
        'payment_method',
        'payment_date',
        'receipt_number',
        'invoice_document_id',
        'notes',
        'term_label',
        'external_payment_system',
        'external_student_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public ?int $marzPayChargeAmount = null;

    protected static function booted()
    {
        static::creating(function ($fee) {
            $fee->uuid = Str::uuid();
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function invoiceDocument()
    {
        return $this->belongsTo(StudentDocument::class, 'invoice_document_id');
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function displayTerm(): string
    {
        return trim((string) ($this->term_label ?: $this->term?->name ?: ''));
    }

    public function remainingBalance(): float
    {
        $amount = (float) $this->amount;
        $paid = (float) $this->amount_paid;

        return round($amount - $paid, 2);
    }

    public function arrears(): float
    {
        if ($this->due_date && $this->due_date->isPast() && $this->remainingBalance() > 0) {
            return $this->remainingBalance();
        }

        return 0;
    }

    public function credit(): float
    {
        return max(0, -1 * $this->remainingBalance());
    }

    public function isPayable(): bool
    {
        return $this->remainingBalance() > 0
            && ! in_array($this->payment_status, ['paid', 'waived'], true);
    }

    public function marzPayAmount(): int
    {
        if ($this->marzPayChargeAmount !== null) {
            return max(0, $this->marzPayChargeAmount);
        }

        return (int) round(max($this->remainingBalance(), 0));
    }

    public function marzPayDescription(): string
    {
        $this->loadMissing('student');
        $type = ucfirst((string) ($this->fee_type ?: 'school'));
        $student = $this->student?->full_name ?? 'student';

        return "School fee ({$type}) — {$student}";
    }

    public function marzPayPhoneNumber(): ?string
    {
        $this->loadMissing('student.parentGuardian');

        return $this->student?->parentGuardian?->phone;
    }

    public function applyCompletedPayment(float $amount, string $method, array $meta = []): FeePayment
    {
        $payment = $this->payments()->create([
            'parent_guardian_id' => $meta['parent_guardian_id'] ?? $this->student?->parent_guardian_id,
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
            'receipt_number' => $meta['receipt_number'] ?? ('FEE-'.$this->id.'-'.now()->format('YmdHis')),
            'proof_path' => $meta['proof_path'] ?? null,
            'proof_url' => $meta['proof_url'] ?? null,
            'proof_mime_type' => $meta['proof_mime_type'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'paid_at' => $meta['paid_at'] ?? now(),
        ]);

        $this->refreshTotalsFromPayments($method, $payment->receipt_number);
        $this->finalizeReceiptAndNotify();

        return $payment;
    }

    public function refreshTotalsFromPayments(?string $latestMethod = null, ?string $receiptNumber = null): void
    {
        $paid = (float) $this->payments()->where('status', 'completed')->sum('amount');
        $amount = (float) $this->amount;
        $balance = round($amount - $paid, 2);

        $status = 'pending';
        if ($paid <= 0 && $this->due_date && $this->due_date->isPast()) {
            $status = 'overdue';
        } elseif ($paid > 0 && $balance > 0) {
            $status = 'partial';
        } elseif ($balance <= 0) {
            $status = 'paid';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $status = 'overdue';
        }

        if ($this->payment_status === 'waived') {
            $status = 'waived';
        }

        $this->update([
            'amount_paid' => $paid,
            'balance' => max($balance, 0),
            'payment_status' => $status,
            'payment_date' => $paid > 0 ? now()->toDateString() : $this->payment_date,
            'payment_method' => $this->normalizeStoredMethod($latestMethod) ?? $this->payment_method,
            'receipt_number' => $receiptNumber ?: $this->receipt_number,
        ]);
    }

    public function markOverdueIfNeeded(): bool
    {
        if (! $this->isPayable() || ! $this->due_date?->isPast()) {
            return false;
        }

        if ($this->payment_status === 'overdue') {
            return false;
        }

        $this->update(['payment_status' => 'overdue']);

        return true;
    }

    public function markMarzPayCompleted(PaymentCollection $collection): void
    {
        $paid = (float) ($collection->base_amount ?: $this->remainingBalance());
        $method = $collection->method === 'card' ? 'card' : 'mobile_money';

        $this->applyCompletedPayment($paid, $method, [
            'receipt_number' => $this->receipt_number ?: ('FEE-'.$this->id.'-'.now()->format('YmdHis')),
            'notes' => 'MarzPay '.$collection->reference,
        ]);
    }

    public function markMarzPayFailed(PaymentCollection $collection): void
    {
        if ($this->payment_status === 'paid') {
            return;
        }

        $this->refreshTotalsFromPayments();
    }

    protected function finalizeReceiptAndNotify(): void
    {
        $fresh = $this->fresh(['student.business', 'student.parentGuardian', 'term', 'payments']);

        if (! $fresh) {
            return;
        }

        try {
            app(FeeInvoiceService::class)->generate($fresh, true);
        } catch (\Throwable $e) {
            Log::error('School fee invoice failed after payment', [
                'fee_id' => $this->id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            app(FeeParentNotificationService::class)->notifyPaid($fresh->fresh(['student.parentGuardian', 'term', 'business']) ?? $fresh);
        } catch (\Throwable $e) {
            Log::error('School fee paid notification failed', [
                'fee_id' => $this->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function normalizeStoredMethod(?string $method): ?string
    {
        return match ($method) {
            'card' => 'card',
            'cash' => 'cash',
            'other', 'school_pay', 'sure_pay' => 'other',
            'mtn_mobile_money', 'airtel_money', 'mobile_money' => 'mobile_money',
            default => $method && in_array($method, ['cash', 'mobile_money', 'bank_transfer', 'check', 'card', 'other'], true)
                ? $method
                : null,
        };
    }
}
