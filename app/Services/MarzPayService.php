<?php

namespace App\Services;

use App\Models\MarzPaySetting;
use App\Models\PaymentCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarzPayService
{
    public function authorizationHeader(): string
    {
        $key = (string) config('marzpay.api_key');
        $secret = (string) config('marzpay.api_secret');

        return 'Basic '.base64_encode($key.':'.$secret);
    }

    public function callbackUrl(): string
    {
        return (string) (config('marzpay.callback_url') ?: url('/api/v1/webhooks/marzpay'));
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (Str::startsWith($digits, '256')) {
            return '+'.$digits;
        }

        if (Str::startsWith($digits, '0')) {
            return '+256'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    public function mapPaymentMethod(string $paymentMethod): string
    {
        return $paymentMethod === 'card' ? 'card' : 'mobile_money';
    }

    public function isOnlinePaymentMethod(?string $paymentMethod): bool
    {
        return in_array($paymentMethod, config('marzpay.online_payment_methods', []), true);
    }

    public function initiate(PaymentCollection $collection): array
    {
        $payload = [
            'amount' => (string) $collection->amount,
            'country' => $collection->country ?: config('marzpay.country', 'UG'),
            'reference' => $collection->reference,
            'description' => Str::limit($collection->description ?: 'Quisat payment', 255, ''),
            'callback_url' => $this->callbackUrl(),
        ];

        if ($collection->method === 'card') {
            $payload['method'] = 'card';

            $response = Http::withHeaders([
                'Authorization' => $this->authorizationHeader(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(rtrim((string) config('marzpay.base_url'), '/').'/collect-money', $payload);
        } else {
            $payload['phone_number'] = $this->normalizePhone($collection->phone_number);

            $response = Http::withHeaders([
                'Authorization' => $this->authorizationHeader(),
                'Accept' => 'application/json',
            ])->asForm()->post(rtrim((string) config('marzpay.base_url'), '/').'/collect-money', $payload);
        }

        $body = $response->json() ?? [];
        $collection->update([
            'request_payload' => $payload,
            'status' => data_get($body, 'data.transaction.status', 'processing'),
            'marz_transaction_uuid' => data_get($body, 'data.transaction.uuid'),
            'redirect_url' => data_get($body, 'data.redirect_url'),
            'provider' => data_get($body, 'data.collection.provider'),
        ]);

        if (! $response->successful() || data_get($body, 'status') !== 'success') {
            Log::error('MarzPay collection failed', [
                'reference' => $collection->reference,
                'status' => $response->status(),
                'body' => $body,
            ]);

            $collection->update(['status' => 'failed']);

            return [
                'success' => false,
                'message' => data_get($body, 'message', 'Unable to initiate payment.'),
                'data' => $body,
            ];
        }

        return [
            'success' => true,
            'message' => data_get($body, 'message', 'Payment initiated.'),
            'data' => [
                'reference' => $collection->reference,
                'status' => $collection->status,
                'base_amount' => $collection->base_amount,
                'platform_charge' => $collection->platform_charge,
                'amount' => $collection->amount,
                'redirect_url' => $collection->redirect_url,
                'transaction_uuid' => $collection->marz_transaction_uuid,
            ],
        ];
    }

    public function createAndInitiate(
        object $payable,
        int $baseAmount,
        string $method,
        ?string $phoneNumber = null,
        ?string $description = null,
    ): array {
        $charge = MarzPaySetting::current()->calculateCharge($baseAmount, $method);

        $collection = PaymentCollection::create([
            'payable_type' => $payable::class,
            'payable_id' => $payable->getKey(),
            'base_amount' => $charge['base_amount'],
            'platform_charge' => $charge['platform_charge'],
            'amount' => $charge['total_amount'],
            'currency' => 'UGX',
            'method' => $method,
            'phone_number' => $phoneNumber,
            'country' => config('marzpay.country', 'UG'),
            'description' => $description,
            'status' => 'pending',
        ]);

        return $this->initiate($collection);
    }

    /**
     * Fetch latest status from MarzPay and update the local collection.
     *
     * @see https://wallet.wearemarz.com/documentation/payments
     *
     * @return array{success:bool,message:string,changed?:bool,status?:string}
     */
    public function syncCollectionStatus(PaymentCollection $collection): array
    {
        $identifier = $collection->marz_transaction_uuid ?: $collection->reference;

        if (! $identifier) {
            return [
                'success' => false,
                'message' => 'No MarzPay reference is available for this transaction.',
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->authorizationHeader(),
            'Accept' => 'application/json',
        ])->get(
            rtrim((string) config('marzpay.base_url'), '/').'/transactions/'.urlencode($identifier)
        );

        $body = $response->json() ?? [];

        if (! $response->successful() || data_get($body, 'status') === 'error') {
            Log::warning('MarzPay status sync failed', [
                'reference' => $collection->reference,
                'identifier' => $identifier,
                'http_status' => $response->status(),
                'body' => $body,
            ]);

            return [
                'success' => false,
                'message' => data_get($body, 'message', 'Unable to fetch transaction status from MarzPay.'),
            ];
        }

        $status = data_get($body, 'transaction.status');

        if (! $status) {
            return [
                'success' => false,
                'message' => 'MarzPay did not return a transaction status.',
            ];
        }

        $previousStatus = $collection->status;

        if ($collection->isFinal() && $previousStatus === $status) {
            return [
                'success' => true,
                'message' => 'Status is already up to date ('.$status.').',
                'changed' => false,
                'status' => $status,
            ];
        }

        $collection->update([
            'status' => $status,
            'marz_transaction_uuid' => data_get($body, 'transaction.uuid', $collection->marz_transaction_uuid),
            'provider' => data_get($body, 'collection.provider', $collection->provider),
            'provider_transaction_id' => data_get(
                $body,
                'collection.provider_transaction_id',
                $collection->provider_transaction_id
            ),
            'callback_payload' => $body,
            'completed_at' => $status === 'completed'
                ? ($collection->completed_at ?? now())
                : $collection->completed_at,
        ]);

        if ($status !== $previousStatus) {
            app(MarzPayPayableResolver::class)->applyCallback($collection->fresh());
        }

        return [
            'success' => true,
            'message' => $status === $previousStatus
                ? 'Status confirmed as '.$status.'.'
                : 'Status updated from '.$previousStatus.' to '.$status.'.',
            'changed' => $status !== $previousStatus,
            'status' => $status,
        ];
    }
}
