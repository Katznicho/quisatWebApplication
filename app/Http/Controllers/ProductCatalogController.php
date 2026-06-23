<?php

namespace App\Http\Controllers;

use App\Services\ProductCatalogService;
use App\Support\MarketplaceHub;
use App\Support\StationeryHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCatalogController extends Controller
{
    public function index(Request $request, ProductCatalogService $catalogService)
    {
        $user = Auth::user();
        $business = $user->business;
        $hub = MarketplaceHub::resolveHub($request, MarketplaceHub::defaultHubForBusiness($business));

        if ((int) $user->business_id !== 1) {
            if (! $business?->hasFeatureByName('KidsMart') && ! $business?->hasFeatureByName(StationeryHub::featureName())) {
                abort(403, 'Marketplace is not enabled for this business.');
            }
            MarketplaceHub::ensureHubAccess($business, $hub);
        }

        $summary = $catalogService->summary((int) $user->business_id, $hub);

        return view('catalog.index', [
            'hub' => $hub,
            'hubLabel' => MarketplaceHub::hubLabel($hub),
            'availableHubs' => (int) $user->business_id === 1
                ? [StationeryHub::KIDZ_MART => 'Kids Mart', StationeryHub::HUB => 'Stationery Hub']
                : MarketplaceHub::availableHubs($business),
            'summary' => $summary,
            'currency' => $business?->currency_code ?? 'UGX',
        ]);
    }

    public function export(Request $request, ProductCatalogService $catalogService)
    {
        $user = Auth::user();
        $business = $user->business;
        $hub = MarketplaceHub::resolveHub($request, MarketplaceHub::defaultHubForBusiness($business));

        if ((int) $user->business_id !== 1) {
            MarketplaceHub::ensureHubAccess($business, $hub);
        }

        $products = $catalogService->catalogQuery((int) $user->business_id, $hub)
            ->orderByDesc('units_sold')
            ->orderBy('name')
            ->get();

        $csv = $catalogService->exportCsv(
            $products,
            MarketplaceHub::hubLabel($hub),
            $business?->currency_code ?? 'UGX',
            (int) $user->business_id === 1
        );

        $filename = 'product_catalog_'.$hub.'_'.now()->format('Y-m-d_His').'.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
