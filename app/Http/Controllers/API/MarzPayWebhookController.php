<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentCollection;
use App\Services\MarzPayPayableResolver;
use App\Services\MarzPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarzPayWebhookController extends Controller
{
    public function handle(Request $request, MarzPayPayableResolver $resolver, MarzPayService $marzPay)
    {
        if ($request->isMethod('GET')) {
            return $this->handleBrowserReturn($request, $resolver, $marzPay);
        }

        return $this->handleWebhook($request, $resolver);
    }

    public function handleWebhook(Request $request, MarzPayPayableResolver $resolver)
    {
        $payload = $request->all();

        Log::info('MarzPay webhook received', [
            'event_type' => $payload['event_type'] ?? null,
            'reference' => data_get($payload, 'transaction.reference'),
            'status' => data_get($payload, 'transaction.status'),
        ]);

        $reference = data_get($payload, 'transaction.reference');
        $status = data_get($payload, 'transaction.status');

        if (! $reference || ! $status) {
            return response()->json(['message' => 'Ignored'], 200);
        }

        $collection = PaymentCollection::query()
            ->where('reference', $reference)
            ->first();

        if (! $collection) {
            $withdrawal = \App\Models\WithdrawalRequest::query()
                ->where('uuid', $reference)
                ->first();

            if ($withdrawal) {
                $this->handleWithdrawalWebhook($withdrawal, $status, $payload);

                return response()->json(['message' => 'Withdrawal updated'], 200);
            }

            Log::warning('MarzPay webhook for unknown reference', ['reference' => $reference]);

            return response()->json(['message' => 'Unknown reference'], 200);
        }

        if ($collection->isFinal() && $collection->status === $status) {
            return response()->json(['message' => 'Already processed'], 200);
        }

        $collection->update([
            'status' => $status,
            'marz_transaction_uuid' => data_get($payload, 'transaction.uuid', $collection->marz_transaction_uuid),
            'provider' => data_get($payload, 'collection.provider', $collection->provider),
            'provider_transaction_id' => data_get($payload, 'collection.provider_transaction_id'),
            'callback_payload' => $payload,
            'completed_at' => $status === 'completed' ? now() : $collection->completed_at,
        ]);

        $resolver->applyCallback($collection);

        return response()->json(['message' => 'OK'], 200);
    }

    public function handleBrowserReturn(Request $request, MarzPayPayableResolver $resolver, MarzPayService $marzPay)
    {
        $query = $request->query();

        Log::info('MarzPay browser return received', $query);

        $vendorId = (string) ($query['VendorID'] ?? $query['vendor_id'] ?? '');
        $tranId = (string) ($query['TranID'] ?? $query['tran_id'] ?? '');
        $gatewayStatus = strtoupper((string) ($query['Status'] ?? ''));
        $gatewayReason = (string) ($query['Reason'] ?? '');

        $collection = $this->findCollectionFromBrowserReturn($vendorId, $tranId);

        if ($collection) {
            $sync = $marzPay->syncCollectionStatus($collection);
            $collection->refresh();

            if (! $sync['success'] && $this->isGatewaySuccess($gatewayStatus)) {
                $this->applyBrowserReturnFallback($collection, $resolver, $query, $tranId);
                $collection->refresh();
            }
        }

        $success = $collection && $collection->status === 'completed';
        $pending = ! $success && ($this->isGatewaySuccess($gatewayStatus) || ($collection && ! $collection->isFinal()));

        return response()->view('marzpay.return', [
            'success' => $success,
            'pending' => $pending,
            'message' => $success
                ? null
                : ($gatewayReason ?: 'Your card payment could not be completed.'),
            'reference' => $collection?->reference ?: ($vendorId ?: null),
            'amount' => $collection?->amount,
            'currency' => $collection?->currency ?? 'UGX',
            'tranId' => $tranId ?: $collection?->provider_transaction_id,
        ]);
    }

    protected function findCollectionFromBrowserReturn(string $vendorId, string $tranId): ?PaymentCollection
    {
        if ($vendorId !== '') {
            $byVendor = PaymentCollection::query()
                ->where(function ($query) use ($vendorId) {
                    $query->where('marz_transaction_uuid', $vendorId)
                        ->orWhere('reference', $vendorId);
                })
                ->latest('id')
                ->first();

            if ($byVendor) {
                return $byVendor;
            }
        }

        if ($tranId === '') {
            return null;
        }

        return PaymentCollection::query()
            ->where('provider_transaction_id', $tranId)
            ->latest('id')
            ->first();
    }

    protected function applyBrowserReturnFallback(
        PaymentCollection $collection,
        MarzPayPayableResolver $resolver,
        array $query,
        string $tranId,
    ): void {
        if ($collection->isFinal()) {
            return;
        }

        $collection->update([
            'status' => 'completed',
            'provider' => $collection->provider ?: 'card payments',
            'provider_transaction_id' => $tranId ?: $collection->provider_transaction_id,
            'callback_payload' => array_merge($collection->callback_payload ?? [], [
                'browser_return' => $query,
            ]),
            'completed_at' => now(),
        ]);

        $resolver->applyCallback($collection->fresh());
    }

    protected function isGatewaySuccess(string $gatewayStatus): bool
    {
        return in_array($gatewayStatus, ['SUCCESS', 'COMPLETED'], true);
    }

    protected function handleWithdrawalWebhook(\App\Models\WithdrawalRequest $withdrawal, string $status, array $payload): void
    {
        if ($status === 'failed' && $withdrawal->status !== 'failed') {
            app(\App\Services\BusinessWalletService::class)->refundFailedWithdrawal(
                $withdrawal,
                data_get($payload, 'message', 'Withdrawal failed at payment provider.')
            );

            return;
        }

        if (in_array($status, ['completed', 'sandbox'], true) && $withdrawal->status !== 'completed') {
            $withdrawal->update([
                'status' => 'completed',
                'marz_transaction_uuid' => data_get($payload, 'transaction.uuid', $withdrawal->marz_transaction_uuid),
                'provider_reference' => data_get($payload, 'transaction.provider_reference', $withdrawal->provider_reference),
                'processed_at' => $withdrawal->processed_at ?? now(),
            ]);
        }
    }
}
