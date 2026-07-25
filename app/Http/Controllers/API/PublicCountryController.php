<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class PublicCountryController extends Controller
{
    /**
     * Countries available for guest browse — only those with at least one active business.
     */
    public function index(): JsonResponse
    {
        $businesses = Business::query()
            ->where('id', '!=', 1)
            ->where('status', 'active')
            ->get(['country_id', 'country']);

        $countryIds = $businesses
            ->pluck('country_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $legacyCountryNames = $businesses
            ->filter(fn (Business $business) => ! $business->country_id && filled($business->country))
            ->pluck('country')
            ->map(fn (string $name) => strtolower(trim($name)))
            ->unique()
            ->values();

        $countries = Country::query()
            ->when(
                $countryIds->isNotEmpty() || $legacyCountryNames->isNotEmpty(),
                function ($query) use ($countryIds, $legacyCountryNames) {
                    $query->where(function ($countryQuery) use ($countryIds, $legacyCountryNames) {
                        if ($countryIds->isNotEmpty()) {
                            $countryQuery->whereIn('id', $countryIds);
                        }

                        foreach ($legacyCountryNames as $countryName) {
                            $countryQuery->orWhereRaw('LOWER(name) = ?', [$countryName]);
                        }
                    });
                },
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'currency_code', 'is_default']);

        return response()->json([
            'success' => true,
            'data' => [
                'countries' => $countries,
            ],
        ]);
    }
}
