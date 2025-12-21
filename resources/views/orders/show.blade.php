<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('orders.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">← Back to Orders</a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Order #{{ $order->order_number }}
                </h2>
            </div>

            @if (session('success'))
                <div class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
                <!-- Order Status Update -->
                <div class="border-b pb-4">
                    <form action="{{ route('orders.update-status', $order) }}" method="POST" class="flex items-end space-x-4">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                            <select name="status" class="w-full border rounded-lg px-4 py-2">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Seller Notes</label>
                            <textarea name="seller_notes" rows="2" class="w-full border rounded-lg px-4 py-2">{{ $order->seller_notes }}</textarea>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Update</button>
                    </form>
                </div>

                <!-- Customer Info -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Customer Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium">{{ $order->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium">{{ $order->customer_email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Phone</p>
                            <p class="font-medium">{{ $order->customer_phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Address</p>
                            <p class="font-medium">{{ $order->customer_address ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Order Items</h3>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            <div class="flex justify-between items-center border-b pb-4">
                                <div>
                                    <p class="font-medium">{{ $item->product_name }}</p>
                                    <p class="text-sm text-gray-500">Quantity: {{ $item->quantity }} × UGX {{ number_format($item->price, 2) }}</p>
                                </div>
                                <p class="font-semibold">UGX {{ number_format($item->subtotal, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-between items-center pt-4 border-t">
                        <span class="text-lg font-semibold">Total:</span>
                        <span class="text-2xl font-bold text-blue-600">UGX {{ number_format($order->total, 2) }}</span>
                    </div>
                </div>

                @if($order->notes)
                <div>
                    <h3 class="font-semibold text-lg mb-2">Customer Notes</h3>
                    <p class="text-gray-600">{{ $order->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

