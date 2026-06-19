<?php

namespace App\Http\Controllers;

use App\Support\MarketplaceHub;
use App\Support\StationeryHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $business = $user->business;
        $hub = MarketplaceHub::resolveHub($request, MarketplaceHub::defaultHubForBusiness($business));

        $hasKidsMart = $business && $business->hasFeatureByName('KidsMart');
        $hasStationery = $business && $business->hasFeatureByName(StationeryHub::featureName());

        if ((int) $user->business_id !== 1) {
            if (! $hasKidsMart && ! $hasStationery) {
                abort(403, 'Marketplace is not enabled for this business.');
            }
            MarketplaceHub::ensureHubAccess($business, $hub);
        }

        return view('orders.index', [
            'hub' => $hub,
            'hubLabel' => MarketplaceHub::hubLabel($hub),
            'availableHubs' => (int) $user->business_id === 1
                ? [StationeryHub::KIDZ_MART => 'Kids Mart', StationeryHub::HUB => 'Stationery Hub']
                : MarketplaceHub::availableHubs($business),
        ]);
    }
}
