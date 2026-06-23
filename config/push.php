<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expo Push Notifications (mobile app)
    |--------------------------------------------------------------------------
    */
    'expo' => [
        'access_token' => env('EXPO_ACCESS_TOKEN'),
        'api_url' => 'https://exp.host/--/api/v2/push/send',
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    | Generate keys: php artisan push:generate-vapid-keys
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'mailto:admin@quisat.com')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
