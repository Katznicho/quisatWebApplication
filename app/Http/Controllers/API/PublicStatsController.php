<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ParentGuardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicStatsController extends Controller
{
    /**
     * Platform-wide registration stats (businesses, schools, parents).
     *
     * Businesses / schools only count Quisat-admin-approved registrations
     * (registration_verified_at set after document review).
     */
    public function index(Request $request)
    {
        try {
            $approvedBusinesses = Business::query()
                ->whereNotNull('registration_verified_at');

            $businessesCount = (clone $approvedBusinesses)->count();

            $schoolsCount = (clone $approvedBusinesses)
                ->whereHas('businessCategory', function ($q) {
                    $q->where('name', 'like', '%school%');
                })
                ->count();

            $pendingBusinessesCount = Business::query()
                ->whereNull('registration_verified_at')
                ->where('id', '!=', 1)
                ->count();

            $parentsCount = ParentGuardian::query()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'businesses' => $businessesCount,
                    'schools' => $schoolsCount,
                    'parents' => $parentsCount,
                    'pending_businesses' => $pendingBusinessesCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicStatsController::index - '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
