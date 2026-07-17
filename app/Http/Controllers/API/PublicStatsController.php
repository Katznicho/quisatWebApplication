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
     */
    public function index(Request $request)
    {
        try {
            $businessesCount = Business::query()->count();

            $schoolsCount = Business::query()
                ->whereHas('businessCategory', function ($q) {
                    $q->where('name', 'like', '%school%');
                })
                ->count();

            $parentsCount = ParentGuardian::query()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'businesses' => $businessesCount,
                    'schools' => $schoolsCount,
                    'parents' => $parentsCount,
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
