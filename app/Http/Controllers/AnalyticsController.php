<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Business;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('analytics.index');
    }

    public function showBusiness($business)
    {
        // Handle both UUID and ID for backward compatibility
        if (is_numeric($business)) {
            $business = Business::findOrFail($business);
        } else {
            $business = Business::where('uuid', $business)->firstOrFail();
        }

        return view('analytics.business-show', [
            'business' => $business->load(['users']),
        ]);
    }
}
