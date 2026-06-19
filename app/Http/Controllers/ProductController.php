<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\MarketplaceHub;
use App\Support\ProductCategory;
use App\Support\StationeryHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $business = Auth::user()->business;
        $hub = MarketplaceHub::resolveHub($request, MarketplaceHub::defaultHubForBusiness($business));
        MarketplaceHub::ensureHubAccess($business, $hub);

        $products = Product::where('business_id', $business->id ?? 0)
            ->where('hub', $hub)
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->get();

        $lowStockCount = $products->filter(fn (Product $p) => $p->isLowStock())->count();

        return view('products.index', [
            'products' => $products,
            'hub' => $hub,
            'hubLabel' => MarketplaceHub::hubLabel($hub),
            'availableHubs' => MarketplaceHub::availableHubs($business),
            'lowStockCount' => $lowStockCount,
        ]);
    }

    public function create(Request $request)
    {
        $business = Auth::user()->business;
        $hub = MarketplaceHub::resolveHub($request, MarketplaceHub::defaultHubForBusiness($business));
        MarketplaceHub::ensureHubAccess($business, $hub);

        return view('products.create', [
            'categories' => MarketplaceHub::categoriesForHub($hub),
            'hub' => $hub,
            'hubLabel' => MarketplaceHub::hubLabel($hub),
            'gradeOptions' => StationeryHub::gradeOptions(),
            'qualityOptions' => StationeryHub::qualityOptions(),
            'isStationery' => $hub === StationeryHub::HUB,
        ]);
    }

    public function store(Request $request)
    {
        $business = Auth::user()->business;
        $hub = MarketplaceHub::resolveHub($request, MarketplaceHub::defaultHubForBusiness($business));
        MarketplaceHub::ensureHubAccess($business, $hub);

        $categoryRule = $hub === StationeryHub::HUB
            ? ['nullable', Rule::in(StationeryHub::categories())]
            : ['nullable', Rule::in(ProductCategory::categories())];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => $categoryRule,
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'delivery_days' => 'nullable|integer|min:1|max:30',
            'quality_grade' => ['nullable', Rule::in(array_keys(StationeryHub::qualityOptions()))],
            'grade_levels' => 'nullable|array',
            'grade_levels.*' => Rule::in(array_keys(StationeryHub::gradeOptions())),
            'is_available' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'sizes' => 'nullable|string',
        ]);

        $validated['business_id'] = $business->id ?? null;
        $validated['hub'] = $hub;
        $validated['is_available'] = $request->has('is_available');
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['grade_levels'] = StationeryHub::normalizeGrades($validated['grade_levels'] ?? []);
        $validated['delivery_days'] = $validated['delivery_days'] ?? 3;
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 15;

        if ($request->hasFile('image_path')) {
            $validated['image_path'] = $request->file('image_path')->store('products', 'public');
        }

        $validated['sizes'] = $this->parseSizes($validated['sizes'] ?? null);

        $product = Product::create($validated);

        $this->storeAdditionalImages($request, $product);

        return redirect()->route('products.index', ['hub' => $hub])
            ->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        $this->authorizeProduct($product);
        $product->load('images', 'business');

        return view('products.show', [
            'product' => $product,
            'hub' => $product->hub ?? StationeryHub::KIDZ_MART,
            'hubLabel' => MarketplaceHub::hubLabel($product->hub ?? StationeryHub::KIDZ_MART),
        ]);
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);
        $product->load('images');
        $hub = $product->hub ?? StationeryHub::KIDZ_MART;

        return view('products.edit', [
            'product' => $product,
            'categories' => MarketplaceHub::categoriesForHub($hub),
            'hub' => $hub,
            'hubLabel' => MarketplaceHub::hubLabel($hub),
            'gradeOptions' => StationeryHub::gradeOptions(),
            'qualityOptions' => StationeryHub::qualityOptions(),
            'isStationery' => $hub === StationeryHub::HUB,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        $hub = $product->hub ?? StationeryHub::KIDZ_MART;

        $categoryRule = $hub === StationeryHub::HUB
            ? ['nullable', Rule::in(StationeryHub::categories())]
            : ['nullable', Rule::in(ProductCategory::categories())];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => $categoryRule,
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'delivery_days' => 'nullable|integer|min:1|max:30',
            'quality_grade' => ['nullable', Rule::in(array_keys(StationeryHub::qualityOptions()))],
            'grade_levels' => 'nullable|array',
            'grade_levels.*' => Rule::in(array_keys(StationeryHub::gradeOptions())),
            'is_available' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'sizes' => 'nullable|string',
        ]);

        $validated['is_available'] = $request->has('is_available');
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['grade_levels'] = StationeryHub::normalizeGrades($validated['grade_levels'] ?? []);
        $validated['delivery_days'] = $validated['delivery_days'] ?? $product->delivery_days ?? 3;
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? $product->low_stock_threshold ?? 15;

        if ($request->hasFile('image_path')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image_path')->store('products', 'public');
        }

        $validated['sizes'] = $this->parseSizes($validated['sizes'] ?? null);

        $product->update($validated);
        $this->storeAdditionalImages($request, $product);

        return redirect()->route('products.index', ['hub' => $hub])
            ->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $hub = $product->hub ?? StationeryHub::KIDZ_MART;

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        foreach ($product->images as $image) {
            if ($image->image_url) {
                Storage::disk('public')->delete($image->image_url);
            }
            $image->delete();
        }

        $product->delete();

        return redirect()->route('products.index', ['hub' => $hub])
            ->with('success', 'Product deleted successfully!');
    }

    protected function authorizeProduct(Product $product): void
    {
        $business = Auth::user()->business;

        if (! $business || (int) $product->business_id !== (int) $business->id) {
            abort(403);
        }
    }

    protected function parseSizes(?string $sizes): array
    {
        if (empty($sizes)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $sizes))));
    }

    protected function storeAdditionalImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $path,
                'is_primary' => $index === 0 && ! $product->image_path,
                'sort_order' => $index,
            ]);
        }
    }
}
