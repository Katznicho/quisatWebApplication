<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List all active products (public)
     */
    public function index(Request $request)
    {
        try {
            $query = Product::query()
                ->with(['business:id,name,email,phone', 'images'])
                ->where('status', 'active')
                ->where('is_available', true)
                ->orderBy('created_at', 'desc');

            // Filter by category
            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }

            // Filter by business
            if ($businessId = $request->query('business_id')) {
                $query->where('business_id', $businessId);
            }

            // Search
            if ($search = $request->query('search')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // In stock only
            if ($request->boolean('in_stock_only')) {
                $query->where('stock_quantity', '>', 0);
            }

            $products = $query->get()->map(function (Product $product) {
                return $this->transformProduct($product);
            });

            $categories = Product::where('status', 'active')
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully.',
                'data' => [
                    'products' => $products,
                    'total' => $products->count(),
                    'categories' => $categories,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single product (public)
     */
    public function show($id)
    {
        try {
            $product = Product::with(['business:id,name,email,phone', 'images'])
                ->where('status', 'active')
                ->where(function ($q) use ($id) {
                    $q->where('uuid', $id);
                    if (is_numeric($id)) {
                        $q->orWhere('id', $id);
                    }
                })
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully.',
                'data' => [
                    'product' => $this->transformProduct($product, true),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform product for API response
     */
    protected function transformProduct(Product $product, bool $includeDetails = false): array
    {
        // Get primary image
        $primaryImage = $product->images->where('is_primary', true)->first();
        $imageUrl = null;
        if ($primaryImage) {
            $imageUrl = str_starts_with($primaryImage->image_url, 'http') 
                ? $primaryImage->image_url 
                : asset('storage/' . $primaryImage->image_url);
        } elseif ($product->image_url) {
            $imageUrl = str_starts_with($product->image_url, 'http') 
                ? $product->image_url 
                : asset('storage/' . $product->image_url);
        }

        // Transform all images
        $images = $product->images->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => str_starts_with($image->image_url, 'http') 
                    ? $image->image_url 
                    : asset('storage/' . $image->image_url),
                'is_primary' => (bool) $image->is_primary,
                'sort_order' => $image->sort_order,
            ];
        })->sortBy('sort_order')->values();

        $data = [
            'id' => $product->id,
            'uuid' => $product->uuid,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'category' => $product->category,
            'image_url' => $imageUrl, // Primary image for backward compatibility
            'images' => $images, // All images array
            'stock_quantity' => $product->stock_quantity,
            'is_available' => (bool) $product->is_available,
            'status' => $product->status,
            'sku' => $product->sku,
            'business' => $product->business ? [
                'id' => $product->business->id,
                'name' => $product->business->name,
                'email' => $product->business->email,
                'phone' => $product->business->phone,
            ] : null,
        ];

        if ($includeDetails) {
            $data['created_at'] = $product->created_at->toIso8601String();
            $data['updated_at'] = $product->updated_at->toIso8601String();
        }

        return $data;
    }
}
