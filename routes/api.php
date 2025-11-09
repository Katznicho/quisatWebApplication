<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\StudentController;

// API Routes
Route::prefix('v1')->group(function () {
    
    // Authentication Routes (Public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('parent-login', [AuthController::class, 'parentLogin']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('parent-forgot-password', [AuthController::class, 'parentForgotPassword']);
        Route::post('parent-reset-password', [AuthController::class, 'parentResetPassword']);
        Route::post('check-parent-email', [AuthController::class, 'checkParentEmail']); // Debug endpoint
    });

    // Protected Routes (Require Authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Authentication Routes (Protected)
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('profile', [AuthController::class, 'profile']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });

        // Business-scoped Routes (Require Authentication + Business Association)
        Route::middleware('business.scope')->group(function () {
            Route::prefix('students')->group(function () {
                Route::get('classes', [StudentController::class, 'classes']);
                Route::get('/', [StudentController::class, 'index']);
                Route::get('{student}', [StudentController::class, 'show']);
            });
        });
    });

    // Payment Routes
    include_once __DIR__ . '/custom/airtel_routes.php';
    include_once __DIR__ . '/custom/mtn_routes.php';
});