<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('KidsMart Orders') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Manage customer orders</p>
            </div>

            @if (session('success'))
                <div class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
                </div>
                <div class="bg-yellow-50 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Pending</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                </div>
                <div class="bg-blue-50 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Confirmed</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['confirmed'] }}</div>
                </div>
                <div class="bg-purple-50 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Processing</div>
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['processing'] }}</div>
                </div>
                <div class="bg-indigo-50 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Shipped</div>
                    <div class="text-2xl font-bold text-indigo-600">{{ $stats['shipped'] }}</div>
                </div>
                <div class="bg-green-50 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Delivered</div>
                    <div class="text-2xl font-bold text-green-600">{{ $stats['delivered'] }}</div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($orders as $order)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $order->order_number }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $order->customer_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">UGX {{ number_format($order->total, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                                            @elseif($order->status == 'processing') bg-purple-100 text-purple-800
                                            @elseif($order->status == 'shipped') bg-indigo-100 text-indigo-800
                                            @elseif($order->status == 'delivered') bg-green-100 text-green-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('orders.show', $order) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No orders found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

