<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\AcademicCalendarController;
use App\Http\Controllers\API\ParentGuardianController;
use App\Http\Controllers\API\KidsProgramController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\ClassAssignmentController;
use App\Http\Controllers\API\ConversationController;

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

            Route::prefix('calendar')->group(function () {
                Route::get('events', [AcademicCalendarController::class, 'index']);
                Route::get('events/{event}', [AcademicCalendarController::class, 'show']);
            });

            Route::prefix('parents')->group(function () {
                Route::get('/', [ParentGuardianController::class, 'index']);
                Route::get('{parent}', [ParentGuardianController::class, 'show']);
            });

            Route::prefix('programs')->group(function () {
                Route::get('/', [KidsProgramController::class, 'index']);
                Route::get('{program}', [KidsProgramController::class, 'show']);
            });

            Route::prefix('announcements')->group(function () {
                Route::get('/', [AnnouncementController::class, 'index']);
                Route::get('{announcement}', [AnnouncementController::class, 'show']);
            });

            Route::prefix('assignments')->group(function () {
                Route::get('/', [ClassAssignmentController::class, 'index']);
                Route::get('{assignment}', [ClassAssignmentController::class, 'show']);
            });

            Route::prefix('conversations')->group(function () {
                Route::get('/', [ConversationController::class, 'index']);
                Route::get('{conversation}/messages', [ConversationController::class, 'messages']);
                Route::post('{conversation}/messages', [ConversationController::class, 'storeMessage']);
                Route::post('{conversation}/read', [ConversationController::class, 'markAsRead']);
            });
        });
    });

    // Payment Routes
    include_once __DIR__ . '/custom/airtel_routes.php';
    include_once __DIR__ . '/custom/mtn_routes.php';
});