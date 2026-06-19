<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Kids Mart Orders</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            View customer orders, track payment status, and update fulfillment progress.
                        </p>
                    </div>
                    <a href="{{ route('products.index') }}"
                        class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Manage products
                    </a>
                </div>

                @livewire('orders.list-orders')
            </div>
        </div>
    </div>
</x-app-layout>
