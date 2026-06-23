<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @include('marketplace._hub-tabs', [
                    'availableHubs' => $availableHubs ?? [],
                    'hub' => $hub ?? 'kidz_mart',
                    'routeName' => 'reviews.index',
                ])

                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $hubLabel ?? 'Kids Mart' }} Customer Feedback</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Product and shop ratings from verified customers. Hide inappropriate feedback if needed.
                    </p>
                </div>

                @livewire('reviews.list-reviews', ['hub' => $hub ?? 'kidz_mart'])
            </div>
        </div>
    </div>
</x-app-layout>
