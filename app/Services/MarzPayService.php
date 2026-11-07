<?php

namespace App\Services;

use App\Models\MarzPaySetting;
use App\Models\PaymentCollection;
use App\Models\PocketPayGatewaySetting;
use App\Models\PocketPayTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarzPayService
{
    protected ?PocketPayGatewaySetting $pocketCredentials = null;

    /**
     * Use the Pocket Pay Marz Pay account. Charges and the rest of the flow stay the same.
     */
    public function forPocketPay(): static
    {
        $clone = clone $this;
        $clone->pocketCredentials = PocketPayGatewaySetting::current();

        return $clone;
    }

    public function authorizationHeader(): string
    {
        return 'Basic '.base64_encode($this->apiKey().':'.$this->apiSecret());
    }

    public function callbackUrl(): string
    {
        return (string) (config('marzpay.callback_url') ?: url('/api/v1/webhooks/marzpay'));
    }

    /**
     * Marz Pay web signing: HMAC-SHA256 of "{timestamp}.{raw_body}" with the account signing secret.
     * Headers: X-MarzPay-Timestamp and X-MarzPay-Signature (t={timestamp},v1={hex}).
     */
    public function webhookSignatureIsValid(Request $request, string $secret): bool
    {
        $timestamp = trim((string) $request->header('X-MarzPay-Timestamp', ''));
        $header = trim((string) $request->header('X-MarzPay-Signature', ''));
        $raw = $request->getContent();

        if ($secret === '' || $timestamp === '' || $header === '' || $raw === '') {
            return false;
        }

        if (! preg_match('/v1=([a-f0-9]+)/i', $header, $matches)) {
            return false;
        }

        if (preg_match('/(?:^|,)t=([^,]+)/', $header, $stamp) && trim($stamp[1]) !== $timestamp) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$raw, $secret);

        return hash_equals($expected, strtolower($matches[1]));
    }

    protected function baseUrl(): string
    {
        $url = $this->pocketCredentials
            ? $this->pocketCredentials->payBaseUrl()
            : config('marzpay.base_url');

        return rtrim((string) $url, '/');
    }

    /**
     * Mobile money wallet on the selected Marz Pay account.
     *
     * @return array{success:bool,message:string,business_name?:string,currency?:string,available?:float,updated_at?:string}
     */
    public function walletBalance(bool $fresh = false): array
    {
        if (! $this->hasApiCredentials()) {
            return ['success' => false, 'message' => 'Marz Pay keys are not saved.'];
        }

        $cacheKey = 'marzpay.wallet-balance.'.md5($this->baseUrl().'|'.$this->apiKey());
        if (! $fresh && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authorizationHeader(),
                'Accept' => 'application/json',
            ])->timeout(12)->get($this->baseUrl().'/balance', [
                'country' => config('marzpay.country', 'UG'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('MarzPay balance request failed', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Could not reach Marz Pay.'];
        }

        $body = $response->json() ?? [];
        if (! $response->successful() || data_get($body, 'status') !== 'success') {
            return [
                'success' => false,
                'message' => (string) data_get($body, 'message', 'Marz Pay did not return a balance.'),
            ];
        }

        $result = [
            'success' => true,
            'message' => 'Balance updated.',
            'business_name' => (string) data_get($body, 'data.account.business_name', ''),
            'currency' => (string) data_get($body, 'data.account.available_balance.currency', 'UGX'),
            'available' => (float) data_get($body, 'data.account.available_balance.raw', 0),
            'updated_at' => (string) data_get($body, 'data.metadata.last_updated', ''),
        ];

        \Illuminate\Support\Facades\Cache::put($cacheKey, $result, now()->addMinute());

        return $result;
    }

    protected function apiKey(): string
    {
        return $this->pocketCredentials
            ? (string) $this->pocketCredentials->marzpay_api_key
            : (string) config('marzpay.api_key');
    }

    protected function apiSecret(): string
    {
        return $this->pocketCredentials
            ? (string) $this->pocketCredentials->marzpay_api_secret
            : (string) config('marzpay.api_secret');
    }

    protected function hasApiCredentials(): bool
    {
        return $this->apiKey() !== '' && $this->apiSecret() !== '';
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
            ])->post($this->baseUrl().'/collect-money', $payload);
        } else {
            $payload['phone_number'] = $this->normalizePhone($collection->phone_number);

            $response = Http::withHeaders([
                'Authorization' => $this->authorizationHeader(),
                'Accept' => 'application/json',
            ])->asForm()->post($this->baseUrl().'/collect-money', $payload);
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
        if ($this->pocketCredentials && ! $this->pocketCredentials->hasPayCredentials()) {
            return [
                'success' => false,
                'message' => 'Pocket Pay Marz Pay keys are not configured.',
            ];
        }

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
        if (! $this->pocketCredentials && $collection->payable_type === PocketPayTopup::class) {
            return $this->forPocketPay()->syncCollectionStatus($collection);
        }

        if ($this->pocketCredentials && ! $this->pocketCredentials->hasPayCredentials()) {
            return [
                'success' => false,
                'message' => 'Pocket Pay Marz Pay keys are not configured.',
            ];
        }

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
        ])->get($this->baseUrl().'/transactions/'.urlencode($identifier));

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
            'message' => data_get($body, 'message', 'Status updated.'),
            'changed' => $status !== $previousStatus,
            'status' => $status,
        ];
    }

    /**
     * Send money to a mobile money wallet (business withdrawal).
     *
     * @return array{success:bool,message:string,status?:string,transaction_uuid?:string,provider_reference?:string,body?:array}
     */
    public function sendMoney(string $reference, float $amount, string $phoneNumber, ?string $description = null): array
    {
        $payload = [
            'amount' => (string) (int) round($amount),
            'phone_number' => $this->normalizePhone($phoneNumber),
            'country' => config('marzpay.country', 'UG'),
            'reference' => $reference,
            'description' => Str::limit($description ?? 'Quisat wallet withdrawal', 255, ''),
            'callback_url' => $this->callbackUrl(),
        ];

        if (empty($payload['phone_number'])) {
            return [
                'success' => false,
                'message' => 'A valid mobile money phone number is required.',
            ];
        }

        if (! $this->hasApiCredentials()) {
            return [
                'success' => false,
                'message' => 'Mobile money disbursement is not configured. Contact support.',
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->authorizationHeader(),
            'Accept' => 'application/json',
        ])->asForm()->post($this->baseUrl().'/send-money', $payload);

        $body = $response->json() ?? [];
        $status = data_get($body, 'data.transaction.status', data_get($body, 'transaction.status'));
        $successful = $response->successful() && data_get($body, 'status') === 'success';

        if (! $successful) {
            Log::error('MarzPay send-money failed', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'body' => $body,
            ]);

            return [
                'success' => false,
                'message' => data_get($body, 'message', 'Unable to send money to mobile wallet.'),
                'body' => $body,
            ];
        }

        $completedStatuses = ['completed', 'sandbox', 'processing'];

        if (! in_array($status, $completedStatuses, true)) {
            return [
                'success' => false,
                'message' => data_get($body, 'message', 'Withdrawal could not be completed.'),
                'status' => $status,
                'body' => $body,
            ];
        }

        return [
            'success' => true,
            'message' => data_get($body, 'message', 'Withdrawal sent successfully.'),
            'status' => $status,
            'transaction_uuid' => data_get($body, 'data.transaction.uuid', data_get($body, 'transaction.uuid')),
            'provider_reference' => data_get($body, 'data.transaction.provider_reference', data_get($body, 'transaction.provider_reference')),
            'body' => $body,
        ];
    }

    /**
     * Push funds from the main or card wallet to a bank account.
     *
     * @return array{success:bool,message:string,status?:string,reference?:string,transaction_uuid?:string,body?:array}
     */
    public function createBankTransfer(
        string $reference,
        float $amount,
        string $bankName,
        string $accountNumber,
        string $walletSource = 'card',
        ?string $accountName = null,
        ?string $bankBranch = null,
        ?string $description = null,
    ): array {
        $payload = array_filter([
            'amount' => (int) round($amount),
            'description' => Str::limit($description ?? 'Quisat bank withdrawal', 500, ''),
            'bank_name' => $bankName,
            'bank_account_number' => $accountNumber,
            'bank_account_name' => $accountName,
            'bank_branch' => $bankBranch,
            'wallet_source' => $walletSource,
        ], fn ($value) => $value !== null && $value !== '');

        if (! $this->hasApiCredentials()) {
            return [
                'success' => false,
                'message' => 'Bank transfer is not configured. Contact support.',
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->authorizationHeader(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl().'/bank-transfer', $payload);

        $body = $response->json() ?? [];
        $status = data_get($body, 'data.bank_transfer.status', data_get($body, 'bank_transfer.status'));
        $successful = $response->successful() && data_get($body, 'status') === 'success';

        if (! $successful) {
            Log::error('MarzPay bank transfer failed', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'body' => $body,
            ]);

            return [
                'success' => false,
                'message' => data_get($body, 'message', 'Unable to process bank transfer.'),
                'body' => $body,
            ];
        }

        return [
            'success' => true,
            'message' => data_get($body, 'message', 'Bank transfer initiated.'),
            'status' => $status,
            'reference' => data_get($body, 'data.bank_transfer.reference', data_get($body, 'bank_transfer.reference')),
            'transaction_uuid' => data_get($body, 'data.bank_transfer.transaction_uuid', data_get($body, 'bank_transfer.transaction_uuid')),
            'body' => $body,
        ];
    }
}
