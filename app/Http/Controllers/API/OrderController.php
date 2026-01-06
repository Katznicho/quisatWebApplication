<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Create an order (public - supports guest ordering)
     */
    public function store(Request $request)
    {
        try {
            $payload = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'customer_phone' => 'required|string|max:50',
                'customer_address' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:2000',
            ]);

            $items = $payload['items'];
            $productIds = collect($items)->pluck('product_id')->unique()->values();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            // Validate all products exist and belong to a single business (simple fulfillment model)
            $businessIds = $products->pluck('business_id')->filter()->unique()->values();
            if ($businessIds->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please place orders from one shop at a time.',
                ], 422);
            }

            $businessId = $businessIds->first();

            return DB::transaction(function () use ($payload, $items, $products, $businessId) {
                $order = Order::create([
                    'uuid' => (string) Str::uuid(),
                    'order_number' => 'KM-' . strtoupper(Str::random(8)),
                    'business_id' => $businessId,
                    'customer_name' => $payload['customer_name'],
                    'customer_email' => $payload['customer_email'] ?? null,
                    'customer_phone' => $payload['customer_phone'],
                    'customer_address' => $payload['customer_address'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'status' => 'pending',
                    'total_amount' => 0,
                ]);

                $total = 0;
                foreach ($items as $item) {
                    /** @var Product $product */
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \RuntimeException('Product not found: ' . $item['product_id']);
                    }

                    $qty = (int) $item['quantity'];
                    $unit = (float) ($product->price ?? 0);
                    $line = $unit * $qty;
                    $total += $line;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'total_price' => $line,
                        'selected_size' => $item['selected_size'] ?? null,
                    ]);
                }

                $order->update(['total_amount' => $total]);

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'order' => [
                            'id' => $order->id,
                            'uuid' => $order->uuid,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'total_amount' => (float) $order->total_amount,
                        ],
                    ],
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('OrderController@store - ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
