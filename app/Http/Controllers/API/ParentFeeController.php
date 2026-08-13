<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\ParentGuardian;
use App\Services\FeeParentNotificationService;
use App\Services\MarzPayCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParentFeeController extends Controller
{
    public function index(Request $request)
    {
        $parent = $this->parent($request);

        if (! $parent) {
            return $this->forbidden();
        }

        $business = $request->get('business');
        $childIds = $parent->students()
            ->where('business_id', $business->id)
            ->pluck('id');

        $query = Fee::query()
            ->with([
                'student:id,first_name,last_name,student_id,parent_guardian_id',
                'term:id,name,academic_year',
                'invoiceDocument',
                'payments',
            ])
            ->where('business_id', $business->id)
            ->whereIn('student_id', $childIds)
            ->orderByRaw("FIELD(payment_status, 'overdue', 'pending', 'partial', 'paid', 'waived')")
            ->orderBy('due_date');

        if ($request->filled('student_id')) {
            $studentId = (int) $request->student_id;
            if (! $childIds->contains($studentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view fees for this student.',
                ], 403);
            }
            $query->where('student_id', $studentId);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }

        $notifier = app(FeeParentNotificationService::class);
        $fees = $query->get()->each(function (Fee $fee) use ($notifier) {
            if ($fee->markOverdueIfNeeded()) {
                $notifier->notifyOverdue($fee);
            }
        })->map(fn (Fee $fee) => $this->transform($fee))->values();

        $outstanding = $fees
            ->filter(fn (array $fee) => $fee['is_payable'])
            ->sum(fn (array $fee) => $fee['balance']);

        return response()->json([
            'success' => true,
            'message' => 'School fees loaded successfully.',
            'data' => [
                'fees' => $fees,
                'summary' => [
                    'outstanding_balance' => (float) $outstanding,
                    'pending_count' => $fees->where('is_payable', true)->count(),
                    'paid_count' => $fees->where('payment_status', 'paid')->count(),
                    'arrears' => (float) $fees->sum(fn (array $fee) => $fee['arrears']),
                    'credits' => (float) $fees->sum(fn (array $fee) => $fee['credit']),
                ],
            ],
        ]);
    }

    public function pay(Request $request, string $fee)
    {
        $parent = $this->parent($request);

        if (! $parent) {
            return $this->forbidden();
        }

        $business = $request->get('business');
        $record = $this->findAuthorizedFee($parent, $business->id, $fee);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Fee not found.',
            ], 404);
        }

        if (! $record->isPayable()) {
            return response()->json([
                'success' => false,
                'message' => $record->payment_status === 'paid'
                    ? 'This fee is already paid.'
                    : 'This fee cannot be paid right now.',
                'data' => [
                    'fee' => $this->transform($record->load(['student', 'term', 'invoiceDocument', 'payments'])),
                ],
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:mtn_mobile_money,airtel_money,card,cash,other',
            'phone_number' => 'nullable|string|max:30',
            'amount' => 'nullable|numeric|min:1',
            'notes' => 'nullable|string|max:1000',
            'proof' => 'nullable|array',
            'proof.name' => 'required_with:proof|string|max:255',
            'proof.base64' => 'required_with:proof|string',
            'proof.mime_type' => 'nullable|string|max:255',
        ]);

        $requested = (float) ($validated['amount'] ?? $record->remainingBalance());
        $payAmount = min($requested, max($record->remainingBalance(), 0));

        if ($payAmount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Enter an amount to pay.',
            ], 422);
        }

        $method = $validated['payment_method'];

        if (in_array($method, ['cash', 'other'], true)) {
            if ($method === 'other' && empty($validated['proof']['base64'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attach a receipt photo when recording a payment made outside MarzPay.',
                ], 422);
            }

            $proof = $this->storeProof($record, $validated['proof'] ?? null);

            $record->applyCompletedPayment($payAmount, $method, [
                'parent_guardian_id' => $parent->id,
                'notes' => $validated['notes'] ?? null,
                'proof_path' => $proof['path'] ?? null,
                'proof_url' => $proof['url'] ?? null,
                'proof_mime_type' => $proof['mime'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded. Your receipt is available in Fees.',
                'payment_initiated' => false,
                'data' => [
                    'fee' => $this->transform($record->fresh(['student', 'term', 'invoiceDocument', 'payments'])),
                    'payment' => null,
                ],
            ]);
        }

        if (in_array($method, ['mtn_mobile_money', 'airtel_money'], true)
            && empty($validated['phone_number'])
            && empty($record->marzPayPhoneNumber())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is required for mobile money payments.',
            ], 422);
        }

        $record->marzPayChargeAmount = (int) round($payAmount);
        $checkout = app(MarzPayCheckoutService::class);

        try {
            $paymentResult = $checkout->maybeInitiate(
                $record,
                $method,
                $validated['phone_number'] ?? null,
                (int) round($payAmount),
            );
        } catch (\Throwable $e) {
            Log::error('School fee payment initiation failed', [
                'fee_id' => $record->id,
                'message' => $e->getMessage(),
            ]);
            $paymentResult = [
                'success' => false,
                'message' => 'Unable to initiate payment right now.',
            ];
        }

        $paymentMeta = $checkout->registrationPaymentMeta(
            $paymentResult,
            $method,
            'Approve the MarzPay prompt to complete this school fee.',
            'Fee recorded.'
        );

        $status = $paymentMeta['payment_initiated'] ? 200 : 422;

        return response()->json([
            'success' => $paymentMeta['payment_initiated'],
            'message' => $paymentMeta['message'],
            'payment_initiated' => $paymentMeta['payment_initiated'],
            'payment_error' => $paymentMeta['payment_error'],
            'data' => [
                'fee' => $this->transform($record->fresh(['student', 'term', 'invoiceDocument', 'payments'])),
                'payment' => $paymentMeta['payment'],
            ],
        ], $status);
    }

    protected function storeProof(Fee $fee, ?array $proof): array
    {
        if (! $proof || empty($proof['base64'])) {
            return [];
        }

        $base64 = $proof['base64'];
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return [];
        }

        $extension = pathinfo((string) ($proof['name'] ?? 'proof.jpg'), PATHINFO_EXTENSION) ?: 'jpg';
        $path = 'fee-proofs/'.$fee->business_id.'/'.$fee->id.'-'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return [
            'path' => $path,
            'url' => asset('storage/'.$path),
            'mime' => $proof['mime_type'] ?? 'image/jpeg',
        ];
    }

    protected function parent(Request $request): ?ParentGuardian
    {
        $user = $request->get('authenticated_user');

        return $user instanceof ParentGuardian ? $user : null;
    }

    protected function forbidden()
    {
        return response()->json([
            'success' => false,
            'message' => 'Only parents/guardians can access this resource.',
        ], 403);
    }

    protected function findAuthorizedFee(ParentGuardian $parent, int $businessId, string $identifier): ?Fee
    {
        $childIds = $parent->students()
            ->where('business_id', $businessId)
            ->pluck('id');

        return Fee::query()
            ->with(['student.parentGuardian', 'term', 'invoiceDocument', 'payments'])
            ->where('business_id', $businessId)
            ->whereIn('student_id', $childIds)
            ->where(function ($query) use ($identifier) {
                $query->where('uuid', $identifier)->orWhere('id', $identifier);
            })
            ->first();
    }

    public function transform(Fee $fee): array
    {
        $invoice = $fee->invoiceDocument;

        return [
            'id' => $fee->id,
            'uuid' => $fee->uuid,
            'fee_type' => $fee->fee_type,
            'amount' => (float) $fee->amount,
            'amount_paid' => (float) $fee->amount_paid,
            'balance' => max($fee->remainingBalance(), 0),
            'arrears' => $fee->arrears(),
            'credit' => $fee->credit(),
            'due_date' => optional($fee->due_date)->toDateString(),
            'payment_status' => $fee->payment_status,
            'payment_method' => $fee->payment_method,
            'payment_date' => optional($fee->payment_date)->toDateString(),
            'receipt_number' => $fee->receipt_number,
            'notes' => $fee->notes,
            'term_label' => $fee->displayTerm() ?: null,
            'external_payment_system' => $fee->external_payment_system,
            'external_student_code' => $fee->external_student_code,
            'is_payable' => $fee->isPayable(),
            'student' => $fee->student ? [
                'id' => $fee->student->id,
                'full_name' => $fee->student->full_name,
                'student_id' => $fee->student->student_id,
            ] : null,
            'term' => $fee->term ? [
                'id' => $fee->term->id,
                'name' => $fee->term->name,
                'academic_year' => $fee->term->academic_year,
            ] : null,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'title' => $invoice->title,
                'url' => $invoice->file_url ?: ($invoice->file_path ? asset('storage/'.$invoice->file_path) : null),
                'mime_type' => $invoice->mime_type,
            ] : null,
            'payments' => $fee->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'method_label' => $payment->methodLabel(),
                'receipt_number' => $payment->receipt_number,
                'proof_url' => $payment->proof_url,
                'paid_at' => optional($payment->paid_at)->toIso8601String(),
                'notes' => $payment->notes,
            ])->values(),
        ];
    }
}
