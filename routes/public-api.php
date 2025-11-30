<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\API\PublicKidsEventsController;
use App\Http\Controllers\API\PublicAdvertisementsController;
use App\Http\Controllers\API\PublicProgramsController;

// Public API Routes (No Authentication Required)
// These routes use the 'public-api' middleware group which excludes Sanctum

// Test route to verify Laravel is receiving requests
Route::get('test', function() {
    return response()->json([
        'success' => true,
        'message' => 'Test route works! Laravel is receiving API requests.',
        'timestamp' => now()->toIso8601String(),
        'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    ]);
});

// Diagnostic route to check server environment
Route::get('diagnostic', function() {
    try {
        $checks = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_connected' => false,
            'advertisements_table_exists' => false,
            'kids_events_table_exists' => false,
            'programs_table_exists' => false,
            'storage_link_exists' => false,
            'log_writable' => false,
        ];
        
        // Check database connection
        try {
            DB::connection()->getPdo();
            $checks['database_connected'] = true;
        } catch (\Exception $e) {
            $checks['database_error'] = $e->getMessage();
        }
        
        // Check if tables exist
        try {
            $checks['advertisements_table_exists'] = Schema::hasTable('advertisements');
            $checks['kids_events_table_exists'] = Schema::hasTable('kids_events');
            $checks['programs_table_exists'] = Schema::hasTable('programs');
        } catch (\Exception $e) {
            $checks['schema_error'] = $e->getMessage();
        }
        
        // Check storage link
        $checks['storage_link_exists'] = is_link(public_path('storage')) || is_dir(public_path('storage'));
        
        // Check if log is writable
        $checks['log_writable'] = is_writable(storage_path('logs'));
        
        // Try to count records
        try {
            $checks['advertisements_count'] = \App\Models\Advertisement::where('status', 'active')->count();
            $checks['kids_events_count'] = \App\Models\KidsEvent::where('status', '!=', 'cancelled')->count();
            $checks['programs_count'] = \App\Models\Program::where(function($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })->count();
        } catch (\Exception $e) {
            $checks['count_error'] = $e->getMessage();
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

// Christian Kids Hub Programs
Route::get('programs', [PublicProgramsController::class, 'index']);
Route::get('programs/{id}', [PublicProgramsController::class, 'show']);

