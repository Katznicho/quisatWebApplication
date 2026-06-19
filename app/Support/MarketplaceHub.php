<?php

namespace App\Support;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceHub
{
    public static function resolveHub(Request $request, ?string $default = null): string
    {
        $hub = (string) $request->query('hub', $request->input('hub', $default ?? StationeryHub::KIDZ_MART));

        return in_array($hub, [StationeryHub::HUB, StationeryHub::KIDZ_MART], true)
            ? $hub
            : StationeryHub::KIDZ_MART;
    }

    public static function defaultHubForBusiness(?Business $business): string
    {
        if (! $business) {
            return StationeryHub::KIDZ_MART;
        }

        $hasKidsMart = $business->hasFeatureByName('KidsMart');
        $hasStationery = $business->hasFeatureByName(StationeryHub::featureName());

        if ($hasStationery && ! $hasKidsMart) {
            return StationeryHub::HUB;
        }

        return StationeryHub::KIDZ_MART;
    }

    public static function ensureHubAccess(?Business $business, string $hub): void
    {
        if (! $business) {
            abort(403);
        }

        if ($hub === StationeryHub::HUB && ! $business->hasFeatureByName(StationeryHub::featureName())) {
            abort(403, 'Stationery Hub is not enabled for your business.');
        }

        if ($hub === StationeryHub::KIDZ_MART && ! $business->hasFeatureByName('KidsMart')) {
            abort(403, 'Kids Mart is not enabled for your business.');
        }
    }

    public static function hubLabel(string $hub): string
    {
        return $hub === StationeryHub::HUB
            ? 'Stationery Hub'
            : 'Kids Mart';
    }

    public static function availableHubs(?Business $business): array
    {
        if (! $business) {
            return [];
        }

        $hubs = [];

        if ($business->hasFeatureByName('KidsMart')) {
            $hubs[StationeryHub::KIDZ_MART] = 'Kids Mart';
        }

        if ($business->hasFeatureByName(StationeryHub::featureName())) {
            $hubs[StationeryHub::HUB] = 'Stationery Hub';
        }

        return $hubs;
    }

    public static function categoriesForHub(string $hub): array
    {
        if ($hub === StationeryHub::HUB) {
            return array_combine(StationeryHub::categories(), StationeryHub::categories());
        }

        return ProductCategory::options();
    }
}
