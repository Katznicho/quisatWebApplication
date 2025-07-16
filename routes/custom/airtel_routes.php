<?php


use App\Http\Controllers\API\AirtelController;
use Illuminate\Support\Facades\Route;


Route::prefix('airtel')->group(function () {

 Route::any("callback", [AirtelController::class, "airtelCallback"]);
});


