<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MarzPay API credentials
    |--------------------------------------------------------------------------
    |
    | Docs: https://wallet.wearemarz.com/documentation
    |
    */

    'api_key' => env('MARZPAY_API_KEY'),
    'api_secret' => env('MARZPAY_API_SECRET'),
    'base_url' => env('MARZPAY_BASE_URL', 'https://wallet.wearemarz.com/api/v1'),
    'country' => env('MARZPAY_COUNTRY', 'UG'),
    'callback_url' => env('MARZPAY_CALLBACK_URL'),

    'online_payment_methods' => [
        'mtn_mobile_money',
        'airtel_money',
        'card',
    ],

];
