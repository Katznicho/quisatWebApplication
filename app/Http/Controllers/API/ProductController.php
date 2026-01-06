<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query()
                ->with(['business:id,name,email,phone,address,shop_number,website_link,social_media_handles', 'images'])
                ->where(function (Builder $q) {
                    $q->whereNull('status')->orWhere('status', 'active');
                });

            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }

            if ($businessId = $request->query('business_id')) {
                $query->where('business_id', $businessId);
            }

            if (filter_var($request->query('in_stock_only'), FILTER_VALIDATE_BOOL)) {
                $query->where('stock_quantity', '>', 0);
            }

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->map(fn (Product $p) => $this->transformProduct($p, false)),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ProductController@index - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['business:id,name,email,phone,address,shop_number,website_link,social_media_handles', 'images'])
                ->where(function (Builder $q) use ($id) {
                    $q->where('id', $id)->orWhere('uuid', $id);
                })
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $this->transformProduct($product, true),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ProductController@show - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transformProduct(Product $product, bool $includeDetails): array
    {
        $resolveUrl = function (?string $pathOrUrl): ?string {
            if (!$pathOrUrl) {
                return null;
            }
            if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
                return $pathOrUrl;
            }
            return Storage::url($pathOrUrl);
        };

        $images = $product->images
            ? $product->images
                ->sortBy(fn ($img) => [$img->is_primary ? 0 : 1, $img->sort_order, $img->id])
                ->values()
                ->map(function ($img) use ($resolveUrl) {
                    return [
                        'id' => $img->id,
                        'url' => $resolveUrl($img->image_url),
                        'is_primary' => (bool) $img->is_primary,
                        'sort_order' => (int) $img->sort_order,
                    ];
                })
                ->toArray()
            : [];

        $mainImage = $resolveUrl($product->image_path);
        if (!$mainImage && count($images) > 0) {
            $mainImage = $images[0]['url'] ?? null;
        }

        return [
            'id' => $product->id,
            'uuid' => $product->uuid,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price !== null ? (float) $product->price : 0,
            'category' => $product->category,
            'image_url' => $mainImage,
            'images' => $images,
            // For now keep a single "size" field (app uses it), but also provide sizes list
            'size' => is_array($product->sizes) && count($product->sizes) === 1 ? $product->sizes[0] : null,
            'sizes' => $product->sizes ?? [],
            'stock_quantity' => (int) ($product->stock_quantity ?? 0),
            'is_available' => (bool) ($product->is_available ?? true),
            'status' => $product->status ?? 'active',
            'business' => $product->business ? [
                'id' => $product->business->id,
                'name' => $product->business->name,
                'email' => $product->business->email,
                'phone' => $product->business->phone,
                'address' => $product->business->address,
                'shop_number' => $product->business->shop_number,
                'website_link' => $product->business->website_link,
                'social_media_handles' => $product->business->social_media_handles,
            ] : null,
        ];
    }
}
