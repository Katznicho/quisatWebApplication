<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class PublicCountryController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::query()
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
