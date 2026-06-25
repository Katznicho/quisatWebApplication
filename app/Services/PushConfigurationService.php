<?php

namespace App\Services;

class PushConfigurationService
{
    /**
     * @return array<int, array{key: string, label: string, ok: bool, detail: string}>
     */
    public function checks(): array
    {
        $queue = config('queue.default');

        return [
            [
                'key' => 'queue',
                'label' => 'Delivery mode',
                'ok' => true,
                'detail' => $queue === 'sync'
                    ? 'Sending immediately (no queue worker). Keep QUEUE_CONNECTION=sync.'
                    : 'Queue driver: '.$queue.' — broadcasts still send immediately via dispatchSync.',
            ],
            [
                'key' => 'vapid_public',
                'label' => 'Web push (VAPID public key)',
                'ok' => filled(config('push.vapid.public_key')),
                'detail' => filled(config('push.vapid.public_key'))
                    ? 'Configured for browser notifications.'
                    : 'Run php artisan push:generate-vapid-keys and add VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY to .env',
            ],
            [
                'key' => 'vapid_private',
                'label' => 'Web push (VAPID private key)',
                'ok' => filled(config('push.vapid.private_key')),
                'detail' => filled(config('push.vapid.private_key'))
                    ? 'Private key present.'
                    : 'Missing VAPID_PRIVATE_KEY in .env',
            ],
            [
                'key' => 'expo_token',
                'label' => 'Expo push API (optional)',
                'ok' => true,
                'detail' => filled(config('push.expo.access_token'))
                    ? 'EXPO_ACCESS_TOKEN set (higher Expo API limits).'
                    : 'Optional EXPO_ACCESS_TOKEN not set — mobile push still works via Expo public API. Configure FCM/APNs in EAS for Android/iOS delivery.',
            ],
            [
                'key' => 'migrations',
                'label' => 'Database tables',
                'ok' => \Illuminate\Support\Facades\Schema::hasTable('device_tokens')
                    && \Illuminate\Support\Facades\Schema::hasTable('push_broadcasts'),
                'detail' => \Illuminate\Support\Facades\Schema::hasTable('device_tokens')
                    ? 'device_tokens and push_broadcasts tables exist.'
                    : 'Run php artisan migrate on this server.',
            ],
        ];
    }

    public function isReady(): bool
    {
        return collect($this->checks())
            ->reject(fn (array $check) => $check['key'] === 'expo_token')
            ->every(fn (array $check) => $check['ok']);
    }

    /**
     * @return array{total: int, active: int, ios: int, android: int, web: int}
     */
    public function deviceStats(callable $baseQuery): array
    {
        $total = $baseQuery()->count();
        $active = $baseQuery()->where('is_active', true);

        return [
            'total' => $total,
            'active' => (clone $active)->count(),
            'ios' => (clone $active)->where('platform', 'ios')->count(),
            'android' => (clone $active)->where('platform', 'android')->count(),
            'web' => (clone $active)->where('platform', 'web')->count(),
        ];
    }
}
