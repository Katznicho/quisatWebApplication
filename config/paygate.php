<?php

return [
    'wallet' => env('PAYGATE_WALLET', '0xF977814e90dA44bFA03b6295A0616a897441aceC'),
    'callback_url' => env('PAYGATE_CALLBACK', 'https://app.nextgentraderai.com/api/complete-payment'),
];
