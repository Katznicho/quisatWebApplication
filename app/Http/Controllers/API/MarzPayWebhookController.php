<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MarzPayPayableResolver;
use App\Services\MarzPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarzPayWebhookController extends Controller
{
    public function handle(Request $request, MarzPayPayableResolver $resolver)
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

        $collection = \App\Models\PaymentCollection::query()
            ->where('reference', $reference)
            ->first();

        if (! $collection) {
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
}
