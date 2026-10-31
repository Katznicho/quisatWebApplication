<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MarzSMS
    |--------------------------------------------------------------------------
    |
    | Credentials are managed by the super admin on the MarzSMS page and stored
    | encrypted in `marzsms_settings`. The base URL here is only the initial
    | default for a fresh install.
    |
    */

    'base_url' => env('MARZSMS_BASE_URL'),

    'timeout' => (int) env('MARZSMS_TIMEOUT', 20),

];
