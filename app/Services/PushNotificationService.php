<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\PushBroadcast;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public function sendToToken(DeviceToken $token, string $title, string $body, ?array $data = null): bool
    {
        if ($token->isExpo()) {
            return $this->sendExpo($token->push_token, $title, $body, $data);
        }

        if ($token->isWeb()) {
            return $this->sendWebPush($token->push_token, $title, $body, $data);
        }

        return false;
    }

    public function sendExpo(string $expoToken, string $title, string $body, ?array $data = null): bool
    {
        if (! str_starts_with($expoToken, 'ExponentPushToken[') && ! str_starts_with($expoToken, 'ExpoPushToken[')) {
            return false;
        }

        $payload = [
            'to' => $expoToken,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'priority' => 'high',
        ];

        if ($data) {
            $payload['data'] = $data;
        }

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
        $expoTokens = collect($tokens)
            ->filter(fn (DeviceToken $t) => $t->isExpo())
            ->pluck('push_token')
            ->filter()
            ->values()
            ->all();

        if ($expoTokens === []) {
            return ['sent' => 0, 'failed' => 0];
        }

        $messages = array_map(function (string $to) use ($title, $body, $data) {
            $message = [
                'to' => $to,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'priority' => 'high',
            ];

            if ($data) {
                $message['data'] = $data;
            }

            return $message;
        }, $expoTokens);

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
}
