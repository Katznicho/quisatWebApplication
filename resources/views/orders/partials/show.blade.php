<div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">Customer</p>
            <p>{{ $order->customer_name }}</p>
            <p>{{ $order->customer_phone }}</p>
            @if ($order->customer_email)
                <p>{{ $order->customer_email }}</p>
            @endif
        </div>
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">Order details</p>
            <p>Status: <span class="font-medium">{{ ucfirst($order->status) }}</span></p>
            @if (($order->hub ?? 'kidz_mart') === 'stationery_hub')
                <p>Marketplace: <span class="font-medium">Stationery Hub</span></p>
                <p>Fulfillment: <span class="font-medium">{{ \App\Support\StationeryHub::fulfillmentStatuses()[$order->fulfillment_status ?? 'new'] ?? ucfirst($order->fulfillment_status ?? 'new') }}</span></p>
            @else
                <p>Marketplace: <span class="font-medium">Kids Mart</span></p>
            @endif
            <p>Payment: {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'cash')) }} ({{ ucfirst($order->payment_status ?? 'pending') }})</p>
            <p>Total: UGX {{ number_format((float) ($order->total_amount ?? $order->total ?? 0)) }}</p>
            @if ($order->business)
                <p>Business: {{ $order->business->name }}</p>
            @endif
            @if ($order->payment_status === 'paid' && in_array($order->payment_method, ['mtn_mobile_money', 'airtel_money', 'card'], true))
                <p>Funds:
                    @if ($order->funds_released_at)
                        <span class="text-green-600 font-medium">Released {{ $order->funds_released_at->format('M d, Y H:i') }}</span>
                    @else
                        <span class="text-amber-600 font-medium">Held until order is confirmed received</span>
                    @endif
                </p>
            @endif
            <p>Customer receipt:
                @if ($order->customer_received_at)
                    <span class="text-green-600 font-medium">
                        Confirmed {{ $order->customer_received_at->format('M d, Y H:i') }}
                        @if ($order->customerReceivedBy)
                            by {{ $order->customerReceivedBy->name }}
                        @endif
                    </span>
                @else
                    <span class="text-gray-500 font-medium">Awaiting customer confirmation</span>
                @endif
            </p>
            <p>Placed: {{ $order->created_at?->format('M d, Y H:i') }}</p>
        </div>
    </div>

    @if ($order->customer_address)
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">Delivery address</p>
            <p>{{ $order->customer_address }}</p>
        </div>
    @endif

    @if ($order->notes)
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">Customer notes</p>
            <p>{{ $order->notes }}</p>
        </div>
    @endif

    <div>
        <p class="mb-2 font-semibold text-gray-900 dark:text-white">Items</p>
        <div class="overflow-x-auto rounded border border-gray-200 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">Qty</th>
                        <th class="px-3 py-2 text-left">Unit price</th>
                        <th class="px-3 py-2 text-left">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-3 py-2">
                                {{ $item->product_name ?? $item->product?->name ?? 'Product' }}
                                @if ($item->selected_size)
                                    <span class="text-gray-500">({{ $item->selected_size }})</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $item->quantity }}</td>
                            <td class="px-3 py-2">UGX {{ number_format((float) ($item->unit_price ?? $item->price ?? 0)) }}</td>
                            <td class="px-3 py-2">UGX {{ number_format((float) ($item->total_price ?? $item->subtotal ?? 0)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
