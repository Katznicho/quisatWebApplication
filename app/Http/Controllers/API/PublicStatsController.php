<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ParentGuardian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicStatsController extends Controller
{
    /**
     * Platform-wide registration stats (businesses, schools, parents).
     *
     * Counts exclude the internal system business (id = 1).
     * All registered organisations count — not split by admin verification status.
     */
    public function index(Request $request)
    {
        try {
            $platformBusinesses = $this->platformBusinessesQuery();

            $businessesCount = (clone $platformBusinesses)->count();

            $schoolsCount = $this->applySchoolScope(clone $platformBusinesses)->count();

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

    /**
     * Real customer organisations — excludes the internal Quisat/system business.
     */
    protected function platformBusinessesQuery(): Builder
    {
        return Business::query()->where('id', '!=', 1);
    }

    protected function applySchoolScope(Builder $query): Builder
    {
        return $query->where(function (Builder $schoolQuery) {
            $schoolQuery
                ->whereHas('businessCategory', function (Builder $categoryQuery) {
                    $categoryQuery->whereRaw('LOWER(name) LIKE ?', ['%school%']);
                })
                ->orWhereRaw('LOWER(type) LIKE ?', ['%school%']);
        });
    }
}
