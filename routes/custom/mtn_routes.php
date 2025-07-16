<?php


use App\Http\Controllers\API\MTNController;
use Illuminate\Support\Facades\Route;


Route::prefix('airtel')->group(function () {
    Route::any("mtncallback", [MTNController::class, 'mtnCallback']);
});


