<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\BusinessReview;
use App\Support\CustomerOrderMatcher;
use App\Support\ReviewAuthor;
use App\Services\MarzPayCheckoutService;
use App\Services\BusinessWalletService;
use App\Support\StationeryHub;
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
                'payment_method' => 'nullable|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
            ]);

            Log::info('OrderController@store - Validation passed', [
                'items_count' => count($payload['items']),
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
            ]);

            $items = $payload['items'];
            $productIds = collect($items)->pluck('product_id')->unique()->values();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($items as $item) {
                if (! $products->has($item['product_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more products are no longer available.',
                    ], 422);
                }
            }

            $hubs = $products->pluck('hub')->filter()->unique()->values();
            if ($hubs->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please place orders from one marketplace at a time.',
                ], 422);
            }

            $hub = $hubs->first() ?? StationeryHub::KIDZ_MART;

            $itemsByBusiness = collect($items)->groupBy(
                fn (array $item) => (int) $products->get($item['product_id'])->business_id
            );

            return DB::transaction(function () use ($payload, $itemsByBusiness, $products, $hub) {
                $createdOrders = [];
                $payments = [];
                $paymentErrors = [];
                $anyPaymentInitiated = false;

                foreach ($itemsByBusiness as $businessId => $businessItems) {
                    $result = $this->createOrderForBusiness(
                        $payload,
                        $businessItems->values()->all(),
                        $products,
                        (int) $businessId,
                        $hub
                    );

                    $createdOrders[] = $result['order'];
                    if ($result['payment']) {
                        $payments[] = $result['payment'];
                    }
                    if ($result['payment_error']) {
                        $paymentErrors[] = $result['payment_error'];
                    }
                    if ($result['payment_initiated']) {
                        $anyPaymentInitiated = true;
                    }
                }

                $ordersCount = count($createdOrders);
                $orderNumbers = collect($createdOrders)->pluck('order_number')->implode(', ');
                $paymentMethod = $payload['payment_method'] ?? 'cash';
                $isOnline = in_array($paymentMethod, ['card', 'mtn_mobile_money', 'airtel_money'], true);

                if ($ordersCount > 1) {
                    $message = $isOnline
                        ? "{$ordersCount} orders created (one per shop): {$orderNumbers}. Complete payment for each shop."
                        : "{$ordersCount} orders placed (one per shop): {$orderNumbers}. Each seller will contact you.";
                } elseif ($anyPaymentInitiated) {
                    $message = 'Order created. '.($isOnline && $paymentMethod === 'card'
                        ? 'Complete your card payment using the link provided.'
                        : 'Approve the mobile money prompt on your phone to complete payment.');
                } elseif (! empty($paymentErrors)) {
                    $message = 'Order created, but online payment could not be started.';
                } else {
                    $message = 'Order placed successfully';
                }

                $firstOrder = $createdOrders[0] ?? null;

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'payment_initiated' => $anyPaymentInitiated,
                    'payment_error' => $paymentErrors[0] ?? null,
                    'orders_count' => $ordersCount,
                    'data' => [
                        'orders' => $createdOrders,
                        'order' => $firstOrder,
                        'payments' => $payments,
                        'payment' => $payments[0] ?? null,
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

            if ($hub = $request->query('hub')) {
                if (in_array($hub, [StationeryHub::HUB, StationeryHub::KIDZ_MART], true)) {
                    $query->where('hub', $hub);
                }
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Determine if user is staff/business (vendor view) or a customer/parent
            $isStaffOrBusiness = $this->isStaffOrBusinessUser($user);

            if ($isStaffOrBusiness) {
                if ((int) $user->business_id !== 1) {
                    $query->where('business_id', $user->business_id);
                }
            } else {
                $this->scopeOrdersToCustomer($query, $user);
            }

            // Filter by customer email if explicitly provided (for staff to view specific customer orders)
            if ($request->has('customer_email') && $isStaffOrBusiness) {
                $query->where('customer_email', $request->customer_email);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->map(fn ($order) => $this->formatOrderSummary($order, false, $user)),
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
            if (!$this->userCanAccessOrder($user, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $this->formatOrderSummary($order, true, $user),
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

            // Check if user can manage this order
            if (! $this->userCanManageOrder($user, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order.',
                ], 403);
            }

            $order->update(['status' => $request->status]);

            if ($request->status === 'delivered' && $order->fresh()->fundsAreHeld()) {
                app(BusinessWalletService::class)->releaseOrderFunds($order->fresh(), $user->id);
            }

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

    /**
     * Customer confirms order received (releases held funds when applicable).
     * Vendors may still confirm via the web panel; that path does not set customer_received_at.
     */
    public function confirmReceived(Request $request, $id)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            $order = Order::where(function ($q) use ($id) {
                $q->where('uuid', $id)->orWhere('id', $id);
            })->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            if (! $this->userCanAccessOrder($user, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order.',
                ], 403);
            }

            $isCustomer = $this->customerOwnsOrder($user, $order) && ! $this->isStaffOrBusinessUser($user);
            $auditUserId = $this->auditUserId($user);

            if ($isCustomer) {
                if ($order->customerHasConfirmedReceipt()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have already confirmed receipt of this order.',
                    ], 422);
                }

                if (! $order->customerCanConfirmReceipt()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This order cannot be confirmed as received yet.',
                    ], 422);
                }

                $order->update([
                    'customer_received_at' => now(),
                    'customer_received_by' => $auditUserId,
                    'status' => 'delivered',
                    'fulfillment_status' => ($order->hub ?? StationeryHub::KIDZ_MART) === StationeryHub::HUB
                        ? 'delivered'
                        : $order->fulfillment_status,
                ]);

                if ($order->fundsAreHeld()) {
                    app(BusinessWalletService::class)->releaseOrderFunds($order->fresh(), $auditUserId);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Thank you! Your order has been marked as received.',
                    'data' => [
                        'order' => $this->formatOrderSummary($order->fresh()->load(['items.product', 'business']), true, $user),
                    ],
                ]);
            }

            if (! $this->userCanManageOrder($user, $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the customer or vendor can confirm order receipt.',
                ], 403);
            }

            if (! $order->fundsAreHeld()) {
                return response()->json([
                    'success' => false,
                    'message' => 'There are no held funds to release for this order.',
                ], 422);
            }

            app(BusinessWalletService::class)->releaseOrderFunds($order, $auditUserId);
            $order->update(['status' => 'delivered']);

            return response()->json([
                'success' => true,
                'message' => 'Order confirmed as received. Funds are now available for withdrawal.',
                'data' => [
                    'order' => $this->formatOrderSummary($order->fresh()->load(['items.product', 'business']), true, $user),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Unable to confirm order receipt.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('OrderController@confirmReceived - '.$e->getMessage(), [
                'order' => $id,
                'user_type' => isset($user) ? $user::class : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm order receipt.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Audit columns on orders/ledgers reference users.id (parents use ParentGuardian tokens).
     */
    private function auditUserId($user): ?int
    {
        return $user instanceof \App\Models\User ? $user->id : null;
    }

    private function isStaffOrBusinessUser($user): bool
    {
        if (! $user instanceof \App\Models\User || ! $user->business_id) {
            return false;
        }

        return $user->isAdmin() || $user->isBusinessAdmin() || $user->isStaff();
    }

    private function scopeOrdersToCustomer($query, $user): void
    {
        $email = strtolower(trim((string) ($user->email ?? '')));
        $phoneSuffix = $this->phoneMatchSuffix($user->phone ?? null);

        if ($email === '' && $phoneSuffix === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function ($q) use ($email, $phoneSuffix) {
            if ($email !== '') {
                $q->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);
            }

            if ($phoneSuffix !== null) {
                $like = '%'.$phoneSuffix;
                if ($email !== '') {
                    $q->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                        [$like]
                    );
                } else {
                    $q->whereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                        [$like]
                    );
                }
            }
        });
    }

    private function customerOwnsOrder($user, Order $order): bool
    {
        $email = strtolower(trim((string) ($user->email ?? '')));
        $orderEmail = strtolower(trim((string) ($order->customer_email ?? '')));

        if ($email !== '' && $orderEmail !== '' && $email === $orderEmail) {
            return true;
        }

        $phoneSuffix = $this->phoneMatchSuffix($user->phone ?? null);
        if ($phoneSuffix === null || ! $order->customer_phone) {
            return false;
        }

        $orderDigits = preg_replace('/\D+/', '', (string) $order->customer_phone);

        return $orderDigits !== '' && str_ends_with($orderDigits, $phoneSuffix);
    }

    private function phoneMatchSuffix(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        return strlen($digits) >= 9 ? substr($digits, -9) : $digits;
    }

    private function userCanAccessOrder($user, Order $order): bool
    {
        if ((int) ($user->business_id ?? 0) === 1 && $user instanceof \App\Models\User) {
            return true;
        }

        if ($this->isStaffOrBusinessUser($user)) {
            return (int) $order->business_id === (int) $user->business_id;
        }

        return $this->customerOwnsOrder($user, $order);
    }

    private function userCanManageOrder($user, Order $order): bool
    {
        if ((int) ($user->business_id ?? 0) === 1 && $user instanceof \App\Models\User) {
            return true;
        }

        return $this->isStaffOrBusinessUser($user)
            && (int) $order->business_id === (int) $user->business_id;
    }

    private function formatOrderItem($item, bool $detailed = false): array
    {
        $unitPrice = (float) $item->unit_price;
        $totalPrice = (float) $item->total_price;
        $productName = $item->product->name ?? 'Unknown';

        $data = [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $productName,
            'quantity' => $item->quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'price' => $unitPrice,
            'subtotal' => $totalPrice,
        ];

        if ($detailed) {
            $data['selected_size'] = $item->selected_size;
            $data['product'] = $item->product ? [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'image_url' => $item->product->image_url,
            ] : null;
        }

        return $data;
    }

    private function formatOrderSummary(Order $order, bool $detailed = false, $user = null): array
    {
        $subtotal = (float) ($order->subtotal ?? $order->total_amount ?? 0);
        $total = (float) ($order->total_amount ?? $order->total ?? $subtotal);

        $data = [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'hub' => $order->hub ?? StationeryHub::KIDZ_MART,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'status' => $order->status,
            'fulfillment_status' => $order->fulfillment_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'wallet_credit_amount' => (float) ($order->wallet_credit_amount ?? 0),
            'funds_released_at' => $order->funds_released_at?->toISOString(),
            'funds_held' => $order->fundsAreHeld(),
            'customer_received_at' => $order->customer_received_at?->toISOString(),
            'customer_received_confirmed' => $order->customerHasConfirmedReceipt(),
            'subtotal' => $subtotal,
            'total' => $total,
            'total_amount' => $total,
            'created_at' => $order->created_at?->toISOString(),
            'items' => $order->items->map(fn ($item) => $this->formatOrderItem($item, $detailed)),
        ];

        if ($user) {
            $data['can_confirm_received'] = $this->customerOwnsOrder($user, $order)
                && ! $this->isStaffOrBusinessUser($user)
                && $order->customerCanConfirmReceipt();
        }

        if ($detailed) {
            $data['customer_address'] = $order->customer_address;
            $data['notes'] = $order->notes;
            $data['updated_at'] = $order->updated_at?->toISOString();
            $data['business'] = $order->business ? [
                'id' => $order->business->id,
                'name' => $order->business->name,
                'email' => $order->business->email ?? null,
                'phone' => $order->business->phone ?? null,
                'rating' => $order->business->rating !== null ? (float) $order->business->rating : null,
                'total_ratings' => (int) ($order->business->total_ratings ?? 0),
            ] : null;

            if ($user && $this->customerOwnsOrder($user, $order) && ! $this->isStaffOrBusinessUser($user)) {
                $data = array_merge($data, $this->formatOrderReviewState($order, $user));
            }
        }

        return $data;
    }

    private function formatOrderReviewState(Order $order, $user): array
    {
        $eligible = CustomerOrderMatcher::orderEligibleForReview($order);
        $reviewedItemIds = ProductReview::query()
            ->where('order_id', $order->id)
            ->where(function ($q) use ($user) {
                ReviewAuthor::scopeForUser($q, $user);
            })
            ->pluck('order_item_id')
            ->all();
        $businessReviewed = BusinessReview::query()
            ->where('order_id', $order->id)
            ->where(function ($q) use ($user) {
                ReviewAuthor::scopeForUser($q, $user);
            })
            ->exists();

        return [
            'can_submit_reviews' => $eligible,
            'can_review_business' => $eligible && ! $businessReviewed && $order->business_id,
            'business_review_submitted' => $businessReviewed,
            'reviewable_items' => $order->items->map(function ($item) use ($reviewedItemIds, $eligible) {
                return [
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name ?? $item->product?->name ?? 'Product',
                    'can_review' => $eligible && ! in_array($item->id, $reviewedItemIds, true),
                    'review_submitted' => in_array($item->id, $reviewedItemIds, true),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, selected_size?:string}>  $items
     * @return array{
     *     order: array<string, mixed>,
     *     payment: ?array,
     *     payment_error: ?string,
     *     payment_initiated: bool,
     * }
     */
    private function createOrderForBusiness(
        array $payload,
        array $items,
        $products,
        int $businessId,
        string $hub
    ): array {
        $total = 0;

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $products->get($item['product_id']);
            $qty = (int) $item['quantity'];
            $total += $product->effectivePrice() * $qty;
        }

        $paymentMethod = $payload['payment_method'] ?? 'cash';

        $order = Order::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $businessId,
            'hub' => $hub,
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'] ?? null,
            'customer_phone' => $payload['customer_phone'],
            'customer_address' => $payload['customer_address'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'subtotal' => $total,
            'total' => $total,
            'total_amount' => $total,
        ]);

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $products->get($item['product_id']);
            $qty = (int) $item['quantity'];
            $unit = $product->effectivePrice();
            $line = $unit * $qty;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $qty,
                'price' => $unit,
                'unit_price' => $unit,
                'subtotal' => $line,
                'total_price' => $line,
                'selected_size' => $item['selected_size'] ?? null,
            ]);
        }

        $payment = null;
        $paymentError = null;
        $paymentInitiated = false;

        /** @var MarzPayCheckoutService $checkout */
        $checkout = app(MarzPayCheckoutService::class);
        $paymentResult = $checkout->maybeInitiate($order, $paymentMethod);

        if ($paymentResult) {
            if ($paymentResult['success']) {
                $payment = $paymentResult['data'];
                $paymentInitiated = true;
            } else {
                $order->update(['payment_status' => 'failed']);
                $paymentError = $paymentResult['message'] ?? 'Payment could not be started.';
            }
        }

        return [
            'order' => $this->formatCreatedOrder($order),
            'payment' => $payment,
            'payment_error' => $paymentError,
            'payment_initiated' => $paymentInitiated,
        ];
    }

    private function formatCreatedOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'business_id' => $order->business_id,
            'hub' => $order->hub,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'total_amount' => (float) $order->total_amount,
        ];
    }
}
