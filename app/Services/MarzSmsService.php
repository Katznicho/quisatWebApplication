<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\Business;
use App\Models\MarzSmsSetting;
use App\Models\MarzSmsTopup;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Client for the MarzSMS API (Basic auth with the API key and secret).
 *
 * @see /api/v1/sms/send, /api/v1/account/balance, /api/v1/account/topup on MarzSMS
 */
class MarzSmsService
{
    /** One GSM text. A longer message is billed as a second SMS. */
    public const MAX_LENGTH = 160;

    public function __construct(
        protected MarzPayService $marzPay,
    ) {}

    public function settings(): MarzSmsSetting
    {
        return MarzSmsSetting::current();
    }

    public function isReady(): bool
    {
        return $this->settings()->isReady();
    }

    /**
     * Canonical +256… phone, or null when the number cannot be used for SMS (MarzSMS only sends to Uganda).
     */
    public function normalizePhone(?string $phone): ?string
    {
        $normalized = $this->marzPay->normalizePhone($phone);

        return $normalized !== null && preg_match('/^\+256\d{9}$/', $normalized) ? $normalized : null;
    }

    /**
     * @return array{success:bool,message:string,balance?:float,cost_per_sms?:float}
     */
    public function balance(?MarzSmsSetting $settings = null): array
    {
        $settings ??= $this->settings();

        if (! $settings->hasCredentials()) {
            return ['success' => false, 'message' => 'MarzSMS credentials are not configured.'];
        }

        try {
            $response = $this->client($settings)->get('/account/balance');
        } catch (\Throwable $e) {
            Log::warning('MarzSMS balance request failed', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Could not reach MarzSMS. Check the base URL.'];
        }

        if (! $response->successful() || ! $response->json('success')) {
            return ['success' => false, 'message' => $this->errorMessage($response, 'Unable to fetch balance.')];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'balance' => (float) $response->json('data.balance'),
            'cost_per_sms' => (float) $response->json('data.cost_per_sms'),
        ];
    }

    /**
     * Record an SMS and deliver it in the background. Never throws, so callers
     * (sales, card actions) are not interrupted by SMS problems.
     */
    public function queue(
        ?string $phone,
        string $message,
        string $purpose = 'general',
        ?Business $business = null,
        ?Model $related = null,
        ?int $sentBy = null,
    ): ?SmsMessage {
        $sms = $this->record($phone, $message, $purpose, $business, $related, $sentBy);

        if ($sms && $sms->status === 'pending') {
            SendSmsJob::dispatch($sms->id)->afterCommit();
        }

        return $sms;
    }

    /**
     * Record and deliver immediately, returning the final record (used for test SMS and OTPs).
     */
    public function sendNow(
        ?string $phone,
        string $message,
        string $purpose = 'general',
        ?Business $business = null,
        ?Model $related = null,
        ?int $sentBy = null,
    ): ?SmsMessage {
        $sms = $this->record($phone, $message, $purpose, $business, $related, $sentBy);

        if ($sms && $sms->status === 'pending') {
            $this->deliver($sms);
        }

        return $sms?->fresh();
    }

    public function deliver(SmsMessage $sms): SmsMessage
    {
        if ($sms->status !== 'pending') {
            return $sms;
        }

        $settings = $this->settings();

        if (! $settings->isReady()) {
            $sms->update(['status' => 'skipped', 'error' => 'MarzSMS is not configured.']);

            return $sms;
        }

        try {
            $response = $this->client($settings)->post('/sms/send', [
                'recipient' => $sms->recipient,
                'message' => $sms->message,
            ]);
        } catch (\Throwable $e) {
            Log::error('MarzSMS send failed', ['sms' => $sms->uuid, 'message' => $e->getMessage()]);
            $sms->update(['status' => 'failed', 'error' => Str::limit('Connection error: '.$e->getMessage(), 250, '')]);

            return $sms;
        }

        $body = $response->json() ?? [];
        $result = data_get($body, 'data.results.0', []);
        $sent = $response->successful()
            && data_get($body, 'success') === true
            && data_get($result, 'status') !== 'failed';

        $sms->update([
            'status' => $sent ? 'sent' : 'failed',
            'cost' => $sent ? data_get($result, 'cost', data_get($body, 'data.total_cost')) : null,
            'provider_transaction_id' => data_get($result, 'transaction_id'),
            'error' => $sent ? null : Str::limit($this->errorMessage($response, 'SMS was not sent.'), 250, ''),
            'response_payload' => $body,
            'sent_at' => $sent ? now() : null,
        ]);

        if (! $sent) {
            Log::warning('MarzSMS rejected message', ['sms' => $sms->uuid, 'status' => $response->status(), 'body' => $body]);
        }

        return $sms;
    }

    /**
     * Start a mobile money top-up of the MarzSMS account. The payer approves the prompt on their phone.
     *
     * @return array{success:bool,message:string,topup?:MarzSmsTopup}
     */
    public function initiateTopup(float $amount, string $phone, ?int $initiatedBy = null): array
    {
        $settings = $this->settings();

        if (! $settings->hasCredentials()) {
            return ['success' => false, 'message' => 'Configure MarzSMS credentials before topping up.'];
        }

        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            return ['success' => false, 'message' => 'Enter a valid mobile money number.'];
        }

        $topup = MarzSmsTopup::create([
            'reference' => (string) Str::uuid(),
            'amount' => $amount,
            'phone_number' => $normalized,
            'status' => 'pending',
            'initiated_by' => $initiatedBy,
        ]);

        try {
            $response = $this->client($settings)->post('/account/topup', [
                'amount' => $amount,
                'phone_number' => $normalized,
                'reference' => $topup->reference,
                'description' => 'Quisat MarzSMS top-up',
            ]);
        } catch (\Throwable $e) {
            Log::error('MarzSMS top-up failed', ['reference' => $topup->reference, 'message' => $e->getMessage()]);
            $topup->update(['status' => 'failed', 'message' => 'Could not reach MarzSMS.']);

            return ['success' => false, 'message' => 'Could not reach MarzSMS.', 'topup' => $topup];
        }

        $body = $response->json() ?? [];

        if (! $response->successful() || data_get($body, 'success') !== true) {
            $message = $this->errorMessage($response, 'Top-up could not be started.');
            $topup->update(['status' => 'failed', 'message' => Str::limit($message, 250, ''), 'response_payload' => $body]);

            return ['success' => false, 'message' => $message, 'topup' => $topup];
        }

        $topup->update([
            'status' => data_get($body, 'data.status', 'pending'),
            'charge' => data_get($body, 'data.charge'),
            'total_amount' => data_get($body, 'data.total_amount'),
            'message' => Str::limit((string) data_get($body, 'message'), 250, ''),
            'response_payload' => $body,
        ]);

        return ['success' => true, 'message' => (string) data_get($body, 'message', 'Top-up initiated.'), 'topup' => $topup];
    }

    /**
     * @return array{success:bool,message:string,changed?:bool}
     */
    public function syncTopup(MarzSmsTopup $topup): array
    {
        $settings = $this->settings();

        if (! $settings->hasCredentials()) {
            return ['success' => false, 'message' => 'MarzSMS credentials are not configured.'];
        }

        try {
            $response = $this->client($settings)->get('/account/topup/'.$topup->reference);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Could not reach MarzSMS.'];
        }

        if (! $response->successful() || ! $response->json('success')) {
            return ['success' => false, 'message' => $this->errorMessage($response, 'Unable to fetch top-up status.')];
        }

        $status = (string) $response->json('data.status', $topup->status);
        $changed = $status !== $topup->status;
        $topup->update(['status' => $status, 'response_payload' => $response->json()]);

        return [
            'success' => true,
            'changed' => $changed,
            'message' => $changed ? "Top-up is now {$status}." : "Top-up is still {$status}.",
        ];
    }

    protected function record(
        ?string $phone,
        string $message,
        string $purpose,
        ?Business $business,
        ?Model $related,
        ?int $sentBy,
    ): ?SmsMessage {
        $message = $this->singleSegment($message);
        $recipient = $this->normalizePhone($phone);

        if (! $recipient || $message === '') {
            return null;
        }

        $error = ! $this->isReady() ? 'MarzSMS is not configured.' : null;

        return SmsMessage::create([
            'business_id' => $business?->id,
            'recipient' => $recipient,
            'message' => $message,
            'purpose' => $purpose,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
            'status' => $error ? 'skipped' : 'pending',
            'error' => $error,
            'sent_by' => $sentBy,
        ]);
    }

    /**
     * Keep the text in the GSM alphabet and within one SMS so it is charged once.
     */
    public function singleSegment(string $message): string
    {
        $message = strtr($message, [
            '•' => '*',
            '’' => "'",
            '‘' => "'",
            '“' => '"',
            '”' => '"',
            '–' => '-',
            '—' => '-',
            '…' => '...',
        ]);

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $message);
        $message = $ascii === false ? $message : $ascii;
        $message = preg_replace('/[^\\n\\x20-\\x7E]/', '', $message) ?? '';
        $message = str_replace(['^', '{', '}', '\\', '[', '~', ']', '|'], '', $message);
        $message = trim((string) preg_replace('/[ \\t]+/', ' ', $message));

        if (strlen($message) <= self::MAX_LENGTH) {
            return $message;
        }

        return rtrim(substr($message, 0, self::MAX_LENGTH));
    }

    protected function client(MarzSmsSetting $settings): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $settings->base_url, '/'))
            ->withBasicAuth((string) $settings->api_key, (string) $settings->api_secret)
            ->acceptJson()
            ->timeout(config('marzsms.timeout', 20));
    }

    protected function errorMessage(Response $response, string $fallback): string
    {
        $message = $response->json('message');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return $response->status() === 401 ? 'MarzSMS rejected the API key or secret.' : $fallback;
    }
}
