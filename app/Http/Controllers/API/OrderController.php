<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        try {
            $user = $request->get('authenticated_user');
            $businessId = $request->get('business_id'); // This will be the seller's business from the product

            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'customer_phone' => 'nullable|string|max:255',
                'customer_address' => 'nullable|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            $subtotal = 0;
            $orderItems = [];
            $sellerBusinessId = null;

            // Validate products and calculate totals
            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('status', 'active')
                    ->where('is_available', true)
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Product ID {$item['product_id']} is not available.",
                    ], 400);
                }

                // Check stock
                if ($product->stock_quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$product->name}. Available: {$product->stock_quantity}",
                    ], 400);
                }

                // Get seller's business from first product
                if (!$sellerBusinessId) {
                    $sellerBusinessId = $product->business_id;
                }

                // Ensure all products are from the same seller
                if ($product->business_id !== $sellerBusinessId) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'All products must be from the same seller.',
                    ], 400);
                }

                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product' => $product,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }

            // Get customer ID if user is authenticated
            $customerId = null;
            if ($user instanceof User) {
                $customerId = $user->id;
            }

            // Create order
            $order = Order::create([
                'business_id' => $sellerBusinessId,
                'customer_id' => $customerId,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal, // No tax/shipping for now
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items and update stock
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update product stock
                $item['product']->decrement('stock_quantity', $item['quantity']);
                
                // Mark as out of stock if needed
                if ($item['product']->stock_quantity <= 0) {
                    $item['product']->update(['status' => 'out_of_stock', 'is_available' => false]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully. The seller will contact you soon.',
                'data' => [
                    'order' => $this->transformOrder($order->load('items.product', 'business')),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while placing the order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List orders for authenticated user (their orders as customer)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->get('authenticated_user');
            $businessId = $request->get('business_id');

            $query = Order::with(['items.product', 'business'])
                ->orderBy('created_at', 'desc');

            // If user is authenticated, show their orders
            if ($user instanceof User) {
                $query->where('customer_id', $user->id);
            } else {
                // For guests, filter by email/phone if provided
                if ($email = $request->query('customer_email')) {
                    $query->where('customer_email', $email);
                }
            }

            // Filter by status
            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            $orders = $query->get()->map(function (Order $order) {
                return $this->transformOrder($order);
            });

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully.',
                'data' => [
                    'orders' => $orders,
                    'total' => $orders->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving orders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single order
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->get('authenticated_user');

            $order = Order::with(['items.product', 'business', 'customer'])
                ->where(function ($q) use ($id) {
                    $q->where('uuid', $id);
                    if (is_numeric($id)) {
                        $q->orWhere('id', $id);
                    }
                })
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Check if user has access to this order
            if ($user instanceof User) {
                if ($order->customer_id !== $user->id && $order->business_id !== $user->business_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this order.',
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order retrieved successfully.',
                'data' => [
                    'order' => $this->transformOrder($order, true),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status (for sellers)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->get('authenticated_user');
            $businessId = $request->get('business_id');

            $validated = $request->validate([
                'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
                'seller_notes' => 'nullable|string|max:1000',
            ]);

            $order = Order::where(function ($q) use ($id) {
                $q->where('uuid', $id);
                if (is_numeric($id)) {
                    $q->orWhere('id', $id);
                }
            })->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Check if user is the seller
            if ($order->business_id !== $businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this order.',
                ], 403);
            }

            $order->update([
                'status' => $validated['status'],
                'seller_notes' => $validated['seller_notes'] ?? $order->seller_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => [
                    'order' => $this->transformOrder($order->load('items.product', 'business')),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform order for API response
     */
    protected function transformOrder(Order $order, bool $includeDetails = false): array
    {
        $data = [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'total' => (float) $order->total,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'notes' => $order->notes,
            'seller_notes' => $order->seller_notes,
            'business' => $order->business ? [
                'id' => $order->business->id,
                'name' => $order->business->name,
                'email' => $order->business->email,
                'phone' => $order->business->phone,
            ] : null,
            'items' => $order->items->map(function (OrderItem $item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => (float) $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) $item->subtotal,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'uuid' => $item->product->uuid,
                        'name' => $item->product->name,
                        'image_url' => $item->product->image_url,
                    ] : null,
                ];
            }),
            'created_at' => $order->created_at->toIso8601String(),
        ];

        if ($includeDetails) {
            $data['updated_at'] = $order->updated_at->toIso8601String();
        }

        return $data;
    }
}
