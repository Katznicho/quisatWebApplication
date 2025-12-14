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
use App\Http\Controllers\API\StaffDashboardController;
use App\Http\Controllers\API\ParentDashboardController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\StudentProgressController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\PublicKidsEventsController;
use App\Http\Controllers\API\PublicAdvertisementsController;
use App\Http\Controllers\API\PublicProgramsController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// API Routes
Route::prefix('v1')->group(function () {
    
    // Public Routes (No Authentication Required)
    // Exclude Sanctum middleware to allow public access
    Route::withoutMiddleware([
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class
    ])->group(function () {
        // Test route
        Route::get('test', function() {
            return response()->json([
                'success' => true,
                'message' => 'Test route works!',
                'timestamp' => now()->toIso8601String(),
            ]);
        });
        
        // Diagnostic route
        Route::get('diagnostic', function() {
            try {
                $checks = [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'database_connected' => false,
                    'advertisements_table_exists' => false,
                    'kids_events_table_exists' => false,
                    'programs_table_exists' => false,
                ];
                
                try {
                    DB::connection()->getPdo();
                    $checks['database_connected'] = true;
                } catch (\Exception $e) {
                    $checks['database_error'] = $e->getMessage();
                }
                
                try {
                    $checks['advertisements_table_exists'] = Schema::hasTable('advertisements');
                    $checks['kids_events_table_exists'] = Schema::hasTable('kids_events');
                    $checks['programs_table_exists'] = Schema::hasTable('programs');
                } catch (\Exception $e) {
                    $checks['schema_error'] = $e->getMessage();
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Diagnostic information',
                    'data' => $checks,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error running diagnostic',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
        
        // Kids Events
        Route::get('kids-events', [PublicKidsEventsController::class, 'index']);
        Route::get('kids-events/{id}', [PublicKidsEventsController::class, 'show']);
        
        // Business Advertisements
        Route::get('advertisements', [PublicAdvertisementsController::class, 'index']);
        Route::get('advertisements/{id}', [PublicAdvertisementsController::class, 'show']);
        
        // Christian Kids Hub Programs (public)
        Route::get('programmes', [PublicProgramsController::class, 'index']);
        Route::get('programmes/{id}', [PublicProgramsController::class, 'show']);
    });
    
    
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
                Route::get('{student}/progress', [StudentProgressController::class, 'show']);
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
                Route::post('/', [AnnouncementController::class, 'store']);
                Route::get('{announcement}', [AnnouncementController::class, 'show']);
            });

            Route::prefix('assignments')->group(function () {
                Route::get('/', [ClassAssignmentController::class, 'index']);
                Route::get('{assignment}', [ClassAssignmentController::class, 'show']);
            });

            Route::prefix('conversations')->group(function () {
                Route::get('/', [ConversationController::class, 'index']);
                Route::post('/', [ConversationController::class, 'store']);
                Route::get('{conversation}/messages', [ConversationController::class, 'messages']);
                Route::post('{conversation}/messages', [ConversationController::class, 'storeMessage']);
                Route::post('{conversation}/read', [ConversationController::class, 'markAsRead']);
            });

            Route::get('staff/dashboard', [StaffDashboardController::class, 'index']);
            Route::get('parent/dashboard', [ParentDashboardController::class, 'index']);
            Route::get('attendance/history', [AttendanceController::class, 'studentHistory']);
            Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('attendance/check-out', [AttendanceController::class, 'checkOut']);
            Route::get('documents', [DocumentController::class, 'index']);
        });
    });

    // Payment Routes
    include_once __DIR__ . '/custom/airtel_routes.php';
    include_once __DIR__ . '/custom/mtn_routes.php';
});