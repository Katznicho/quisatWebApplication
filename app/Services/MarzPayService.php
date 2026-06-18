<?php

namespace App\Services;

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
                'redirect_url' => $collection->redirect_url,
                'transaction_uuid' => $collection->marz_transaction_uuid,
            ],
        ];
    }

    public function createAndInitiate(
        object $payable,
        int $amount,
        string $method,
        ?string $phoneNumber = null,
        ?string $description = null,
    ): array {
        $collection = PaymentCollection::create([
            'payable_type' => $payable::class,
            'payable_id' => $payable->getKey(),
            'amount' => $amount,
            'currency' => 'UGX',
            'method' => $method,
            'phone_number' => $phoneNumber,
            'country' => config('marzpay.country', 'UG'),
            'description' => $description,
            'status' => 'pending',
        ]);

        return $this->initiate($collection);
    }
}
