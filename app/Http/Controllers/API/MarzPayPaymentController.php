<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentCollection;
use App\Services\MarzPayPayableResolver;
use App\Services\MarzPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarzPayPaymentController extends Controller
{
    public function collect(
        Request $request,
        MarzPayService $marzPay,
        MarzPayPayableResolver $resolver,
    ) {
        $validated = $request->validate([
            'payable_type' => 'required|in:order,kids_event_registration,parent_corner_registration,program_registration',
            'payable_id' => 'required|string',
            'payment_method' => 'required|in:mtn_mobile_money,airtel_money,card',
            'phone_number' => 'nullable|string|max:30',
        ]);

        if (! $marzPay->isOnlinePaymentMethod($validated['payment_method'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported online payment method.',
            ], 422);
        }

        $payable = $resolver->resolve($validated['payable_type'], $validated['payable_id']);

        if (! $payable) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.',
            ], 404);
        }

        $amount = $resolver->amountFor($payable);

        if ($amount < 500) {
            return response()->json([
                'success' => false,
                'message' => 'This payment does not require online collection or is below the minimum amount (500 UGX).',
            ], 422);
        }

        $method = $marzPay->mapPaymentMethod($validated['payment_method']);
        $phone = $validated['phone_number'] ?? $resolver->phoneFor($payable);

        if ($method === 'mobile_money' && ! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is required for mobile money payments.',
            ], 422);
        }

        $existing = $payable->latestPaymentCollection();
        if ($existing && $existing->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already completed.',
                'data' => [
                    'payment' => $this->transformPayment($existing),
                ],
            ]);
        }

        try {
            $result = $marzPay->createAndInitiate(
                $payable,
                $amount,
                $method,
                $phone,
                $resolver->descriptionFor($payable),
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'payment' => $result['data'],
                    'payable_type' => $validated['payable_type'],
                    'payable_id' => $validated['payable_id'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MarzPay collect error', [
                'message' => $e->getMessage(),
                'payable_type' => $validated['payable_type'],
                'payable_id' => $validated['payable_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initiate payment right now.',
            ], 500);
        }
    }

    public function status(string $reference)
    {
        $collection = PaymentCollection::query()
            ->where('reference', $reference)
            ->first();

        if (! $collection) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment' => $this->transformPayment($collection),
            ],
        ]);
    }

    protected function transformPayment(PaymentCollection $collection): array
    {
        return [
            'reference' => $collection->reference,
            'status' => $collection->status,
            'amount' => $collection->amount,
            'currency' => $collection->currency,
            'method' => $collection->method,
            'redirect_url' => $collection->redirect_url,
            'provider' => $collection->provider,
            'provider_transaction_id' => $collection->provider_transaction_id,
            'completed_at' => $collection->completed_at?->toISOString(),
        ];
    }
}
