<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @include('marketplace._hub-tabs', [
                    'availableHubs' => $availableHubs ?? [],
                    'hub' => $hub ?? 'kidz_mart',
                    'routeName' => 'products.catalog.index',
                ])

                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $hubLabel ?? 'Kids Mart' }} Products Catalog</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Inventory, pricing, and paid sales performance across your product line.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('products.catalog.export', ['hub' => $hub ?? 'kidz_mart']) }}"
                           class="inline-flex items-center rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            <i class="fas fa-file-download mr-2"></i>Download report
                        </a>
                        <a href="{{ route('products.index', ['hub' => $hub ?? 'kidz_mart']) }}"
                           class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Manage products
                        </a>
                    </div>
                </div>

                <div class="mb-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Products</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['products'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">In stock</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['in_stock'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Units in stock</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['units_in_stock'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Units sold</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($summary['units_sold'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 col-span-2 md:col-span-1">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Sales revenue</p>
                        <p class="mt-1 text-xl font-bold text-green-700">{{ $currency ?? 'UGX' }} {{ number_format($summary['sales_revenue'] ?? 0) }}</p>
                    </div>
                </div>

                <p class="mb-3 text-xs text-gray-500">
                    Sales figures include paid, non-cancelled orders only.
                </p>

                @livewire('catalog.list-product-catalog', ['hub' => $hub ?? 'kidz_mart'])
            </div>
        </div>
    </div>
</x-app-layout>
