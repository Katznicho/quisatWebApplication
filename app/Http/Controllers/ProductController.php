<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products for the business
     */
    public function index()
    {
        $products = Product::where('business_id', Auth::user()->business_id)
            ->with('business')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'primary_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'additional_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock_quantity' => 'required|integer|min:0',
            'is_available' => 'boolean',
            'status' => 'required|in:active,inactive,out_of_stock',
            'sku' => 'nullable|string|max:255|unique:products,sku',
        ]);

        $product = Product::create([
            'business_id' => Auth::user()->business_id,
            'created_by' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'category' => $validated['category'] ?? null,
            'image_url' => null, // Keep for backward compatibility
            'stock_quantity' => $validated['stock_quantity'],
            'is_available' => $validated['is_available'] ?? true,
            'status' => $validated['status'],
            'sku' => $validated['sku'] ?? null,
        ]);

        // Handle primary image (main/big image)
        if ($request->hasFile('primary_image')) {
            $imagePath = $request->file('primary_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $imagePath,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
            $product->update(['image_url' => $imagePath]); // For backward compatibility
        }

        // Handle additional images (up to 4)
        if ($request->hasFile('additional_images')) {
            $sortOrder = 1;
            foreach ($request->file('additional_images') as $image) {
                if ($sortOrder > 4) break; // Limit to 4 additional images
                $imagePath = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imagePath,
                    'is_primary' => false,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        $product->load('business', 'creator', 'images');
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        if ($product->business_id !== Auth::user()->business_id) {
            abort(403);
        }
        $product->load('images');
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        if ($product->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'primary_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'additional_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer|exists:product_images,id',
            'stock_quantity' => 'required|integer|min:0',
            'is_available' => 'boolean',
            'status' => 'required|in:active,inactive,out_of_stock',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $product->id,
        ]);

        // Remove selected images
        if ($request->has('remove_images')) {
            foreach ($request->remove_images as $imageId) {
                $image = ProductImage::find($imageId);
                if ($image && $image->product_id === $product->id) {
                    Storage::disk('public')->delete($image->image_url);
                    $image->delete();
                }
            }
        }

        // Handle primary image update
        if ($request->hasFile('primary_image')) {
            // Remove old primary image
            $oldPrimary = $product->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                Storage::disk('public')->delete($oldPrimary->image_url);
                $oldPrimary->delete();
            }

            $imagePath = $request->file('primary_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $imagePath,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
            $validated['image_url'] = $imagePath; // For backward compatibility
        }

        // Handle additional images
        if ($request->hasFile('additional_images')) {
            $existingCount = $product->images()->where('is_primary', false)->count();
            $sortOrder = $existingCount + 1;
            foreach ($request->file('additional_images') as $image) {
                if ($sortOrder > 5) break; // Limit to 5 total images (1 primary + 4 additional)
                $imagePath = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imagePath,
                    'is_primary' => false,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        unset($validated['primary_image'], $validated['additional_images'], $validated['remove_images']);
        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Public products page (for website)
     */
    public function publicIndex(Request $request)
    {
        $query = Product::where('status', 'active')
            ->where('is_available', true)
            ->with('business')
            ->orderBy('created_at', 'desc');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->paginate(12);

        $categories = Product::where('status', 'active')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return view('products.public', compact('products', 'categories'));
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        if ($product->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        // Delete all product images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_url);
            $image->delete();
        }

        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }
}
