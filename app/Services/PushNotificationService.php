<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\PushBroadcast;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public function sendToToken(DeviceToken $token, string $title, string $body, ?array $data = null): bool
    {
        if ($token->isExpo()) {
            $badge = UserNotification::unreadCountFor($token->tokenable_type, $token->tokenable_id);

            return $this->sendExpo($token->push_token, $title, $body, $data, $badge);
        }

        if ($token->isWeb()) {
            return $this->sendWebPush($token->push_token, $title, $body, $data);
        }

        return false;
    }

    public function sendExpo(string $expoToken, string $title, string $body, ?array $data = null, ?int $badge = null): bool
    {
        if (! str_starts_with($expoToken, 'ExponentPushToken[') && ! str_starts_with($expoToken, 'ExpoPushToken[')) {
            return false;
        }

        $payload = $this->buildExpoPayload($expoToken, $title, $body, $data, $badge);

        $request = Http::acceptJson()->asJson();

        if ($accessToken = config('push.expo.access_token')) {
            $request = $request->withToken($accessToken);
        }

        try {
            $response = $request->post(config('push.expo.api_url'), $payload);

            if (! $response->successful()) {
                Log::warning('Expo push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $result = $response->json('data.0');

            if (is_array($result) && ($result['status'] ?? null) === 'error') {
                Log::warning('Expo push error', $result);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Expo push exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function sendWebPush(string $subscriptionJson, string $title, string $body, ?array $data = null): bool
    {
        $publicKey = config('push.vapid.public_key');
        $privateKey = config('push.vapid.private_key');

        if (! $publicKey || ! $privateKey) {
            Log::warning('Web push skipped: VAPID keys not configured');

            return false;
        }

        try {
            $subscriptionData = json_decode($subscriptionJson, true, 512, JSON_THROW_ON_ERROR);

            $auth = [
                'VAPID' => [
                    'subject' => config('push.vapid.subject'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];

            $webPush = new WebPush($auth);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'image' => $data['imageUrl'] ?? null,
                'data' => $data ?? [],
            ], JSON_THROW_ON_ERROR);

            $subscription = Subscription::create($subscriptionData);
            $report = $webPush->sendOneNotification($subscription, $payload);
            $webPush->flush();

            if ($report->isSuccess()) {
                return true;
            }

            Log::warning('Web push failed', [
                'reason' => $report->getReason(),
                'expired' => $report->isSubscriptionExpired(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Web push exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param  iterable<DeviceToken>  $tokens
     */
    public function sendExpoBatch(iterable $tokens, string $title, string $body, ?array $data = null): array
    {
        $messages = collect($tokens)
            ->filter(fn (DeviceToken $t) => $t->isExpo() && $t->push_token)
            ->unique('push_token')
            ->map(function (DeviceToken $token) use ($title, $body, $data) {
                $badge = UserNotification::unreadCountFor($token->tokenable_type, $token->tokenable_id);

                return $this->buildExpoPayload($token->push_token, $title, $body, $data, $badge);
            })
            ->values()
            ->all();

        if ($messages === []) {
            return ['sent' => 0, 'failed' => 0];
        }

        $request = Http::acceptJson()->asJson();

        if ($accessToken = config('push.expo.access_token')) {
            $request = $request->withToken($accessToken);
        }

        $sent = 0;
        $failed = 0;

        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = $request->post(config('push.expo.api_url'), $chunk);

                if (! $response->successful()) {
                    $failed += count($chunk);

                    continue;
                }

                foreach ($response->json('data', []) as $result) {
                    if (($result['status'] ?? null) === 'ok') {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Expo batch push exception', ['message' => $e->getMessage()]);
                $failed += count($chunk);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function deactivateInvalidToken(DeviceToken $token, bool $success): void
    {
        if ($success) {
            $token->update(['last_used_at' => now()]);

            return;
        }

        // Keep token active for transient failures; only Expo "DeviceNotRegistered" handled in broadcast job
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildExpoPayload(string $expoToken, string $title, string $body, ?array $data, ?int $badge = null): array
    {
        $payload = [
            'to' => $expoToken,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => $this->resolveAndroidChannel($data),
        ];

        if ($badge !== null) {
            $payload['badge'] = max(0, $badge);
        }

        $imageUrl = $data['imageUrl'] ?? null;

        if ($imageUrl) {
            $payload['richContent'] = ['image' => $imageUrl];
            $payload['mutableContent'] = true;
        }

        if ($data) {
            $payload['data'] = $this->stringifyExpoData($data);
        }

        return $payload;
    }

    /**
     * Expo iOS payloads require string values in the data map.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function stringifyExpoData(array $data): array
    {
        $stringified = [];

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $stringified[(string) $key] = json_encode($value) ?: '';
                continue;
            }

            if (is_bool($value)) {
                $stringified[(string) $key] = $value ? 'true' : 'false';
                continue;
            }

            $stringified[(string) $key] = $value === null ? '' : (string) $value;
        }

        return $stringified;
    }

    protected function resolveAndroidChannel(?array $data): string
    {
        $type = strtolower((string) ($data['type'] ?? ''));

        if ($type === 'message' || str_contains($type, 'message')) {
            return 'messages';
        }

        return 'default';
    }
}
