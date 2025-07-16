<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {
    include_once __DIR__ . '/custom/airtel_routes.php';
    include_once __DIR__ . '/custom/mtn_routes.php';

});