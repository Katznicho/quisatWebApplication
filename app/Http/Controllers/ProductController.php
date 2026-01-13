<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $business = Auth::user()->business;
        $products = Product::where('business_id', $business->id ?? 0)
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'stock_quantity' => 'required|integer|min:0',
            'is_available' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'sizes' => 'nullable|string',
        ]);

        $business = Auth::user()->business;
        $validated['business_id'] = $business->id ?? null;
        $validated['is_available'] = $request->has('is_available') ? true : false;
        $validated['status'] = $validated['status'] ?? 'active';

        // Handle main image upload
        if ($request->hasFile('image_path')) {
            $validated['image_path'] = $request->file('image_path')->store('products', 'public');
        }

        // Handle sizes (comma-separated string to array)
        if (!empty($validated['sizes'])) {
            $sizes = array_map('trim', explode(',', $validated['sizes']));
            $validated['sizes'] = array_filter($sizes);
        } else {
            $validated['sizes'] = [];
        }

        $product = Product::create($validated);

        // Handle additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        $product->load('images', 'business');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('images');
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'stock_quantity' => 'required|integer|min:0',
            'is_available' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'sizes' => 'nullable|string',
        ]);

        $validated['is_available'] = $request->has('is_available') ? true : false;
        $validated['status'] = $validated['status'] ?? 'active';

        // Handle main image upload
        if ($request->hasFile('image_path')) {
            // Delete old image
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image_path')->store('products', 'public');
        }

        // Handle sizes (comma-separated string to array)
        if (!empty($validated['sizes'])) {
            $sizes = array_map('trim', explode(',', $validated['sizes']));
            $validated['sizes'] = array_filter($sizes);
        } else {
            $validated['sizes'] = [];
        }

        $product->update($validated);

        // Handle additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $path,
                    'is_primary' => false,
                    'sort_order' => $product->images()->max('sort_order') + $index + 1,
                ]);
            }
        }

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        // Delete main image
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        // Delete additional images
        foreach ($product->images as $image) {
            if ($image->image_url) {
                Storage::disk('public')->delete($image->image_url);
            }
            $image->delete();
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }
}
