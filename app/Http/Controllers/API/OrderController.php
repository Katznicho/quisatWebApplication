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
            Log::info('OrderController@store - Request received', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

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

            Log::info('OrderController@store - Validation passed', [
                'items_count' => count($payload['items']),
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
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
                // Calculate total first
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
                }

                // Create order with subtotal, total, and total_amount
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
                    'subtotal' => $total,
                    'total' => $total, // Set total
                    'total_amount' => $total,
                ]);

                // Create order items
                foreach ($items as $item) {
                    /** @var Product $product */
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \RuntimeException('Product not found: ' . $item['product_id']);
                    }

                    $qty = (int) $item['quantity'];
                    $unit = (float) ($product->price ?? 0);
                    $line = $unit * $qty;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'price' => $unit,
                        'unit_price' => $unit,
                        'subtotal' => $line, // Store subtotal (same as total_price for now)
                        'total_price' => $line,
                        'selected_size' => $item['selected_size'] ?? null,
                    ]);
                }

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
            $errorMessages = [];
            foreach ($e->errors() as $field => $messages) {
                $errorMessages[] = $field . ': ' . implode(', ', $messages);
            }
            $errorMessage = implode(' | ', $errorMessages);
            
            Log::error('OrderController@store - Validation failed', [
                'errors' => $e->errors(),
                'error_message' => $errorMessage,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $errorMessage,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('OrderController@store - Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List orders for authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            $query = Order::query()->with(['items.product', 'business']);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Determine if user is a staff/business user or a customer/parent
            $isStaffOrBusiness = $user instanceof \App\Models\User && $user->business_id;
            
            if ($isStaffOrBusiness) {
                // For staff/business users, show orders for their business
                $query->where('business_id', $user->business_id);
            } else {
                // For customers/parents, show orders by their email
                // Use the user's email to find their orders
                $userEmail = $user->email ?? null;
                if ($userEmail) {
                    $query->where('customer_email', $userEmail);
                } else {
                    // If no email, return empty array
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'orders' => [],
                        ],
                    ]);
                }
            }

            // Filter by customer email if explicitly provided (for staff to view specific customer orders)
            if ($request->has('customer_email') && $isStaffOrBusiness) {
                $query->where('customer_email', $request->customer_email);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'uuid' => $order->uuid,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'customer_email' => $order->customer_email,
                            'customer_phone' => $order->customer_phone,
                            'status' => $order->status,
                            'subtotal' => (float) ($order->subtotal ?? 0),
                            'total' => (float) ($order->total ?? 0),
                            'total_amount' => (float) $order->total_amount,
                            'created_at' => $order->created_at?->toISOString(),
                            'items' => $order->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'product_id' => $item->product_id,
                                    'product_name' => $item->product->name ?? 'Unknown',
                                    'quantity' => $item->quantity,
                                    'unit_price' => (float) $item->unit_price,
                                    'total_price' => (float) $item->total_price,
                                ];
                            }),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('OrderController@index - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific order
     */
    public function show($id)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            $order = Order::where(function($q) use ($id) {
                    $q->where('uuid', $id)->orWhere('id', $id);
                })
                ->with(['items.product', 'business'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Check if user has access to this order
            if ($user->business_id && $order->business_id !== $user->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'uuid' => $order->uuid,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'customer_email' => $order->customer_email,
                        'customer_phone' => $order->customer_phone,
                        'customer_address' => $order->customer_address,
                        'notes' => $order->notes,
                        'status' => $order->status,
                        'subtotal' => (float) ($order->subtotal ?? 0),
                        'total' => (float) ($order->total ?? 0),
                        'total_amount' => (float) $order->total_amount,
                        'created_at' => $order->created_at?->toISOString(),
                        'updated_at' => $order->updated_at?->toISOString(),
                        'business' => $order->business ? [
                            'id' => $order->business->id,
                            'name' => $order->business->name,
                        ] : null,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'product' => $item->product ? [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'image_url' => $item->product->image_url,
                                ] : null,
                                'quantity' => $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'total_price' => (float) $item->total_price,
                                'selected_size' => $item->selected_size,
                            ];
                        }),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('OrderController@show - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            $request->validate([
                'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            ]);

            $order = Order::where(function($q) use ($id) {
                    $q->where('uuid', $id)->orWhere('id', $id);
                })
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Check if user has access to this order
            if ($user->business_id && $order->business_id !== $user->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order.',
                ], 403);
            }

            $order->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'uuid' => $order->uuid,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                    ],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('OrderController@updateStatus - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
