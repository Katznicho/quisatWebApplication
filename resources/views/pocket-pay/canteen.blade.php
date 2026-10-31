<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @include('pocket-pay.partials.nav')

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <div>
                        <a href="{{ route('pocket-pay.canteens.index') }}" class="text-sm text-blue-600 hover:text-blue-800">← All canteens</a>
                        <h3 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">
                            {{ $canteen->name }}
                            @unless ($canteen->isActive())
                                <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Inactive</span>
                            @endunless
                        </h3>
                        <p class="text-sm text-gray-500">{{ $canteen->business?->name }}{{ $canteen->location ? ' · '.$canteen->location : '' }}</p>
                    </div>
                </div>

                @if ($canteen->isFloatLow())
                    <div class="mt-4 rounded border border-yellow-300 bg-yellow-50 px-4 py-3 text-yellow-800">
                        The float is below UGX {{ number_format($canteen->low_float_threshold) }}. Sales stop when it can't cover a purchase. Top it up from the Canteens list.
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Float balance</p>
                        <p class="mt-1 text-xl font-bold {{ $canteen->isFloatLow() ? 'text-red-600' : 'text-gray-800 dark:text-white' }}">UGX {{ number_format($canteen->float_balance) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Sales today</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">UGX {{ number_format($salesToday) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Transactions today</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ number_format($salesCountToday) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Top-ups awaiting payment</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">UGX {{ number_format($pendingTopups) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @livewire('pocket-pay.canteen-attendants', ['canteen' => $canteen])
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @livewire('pocket-pay.canteen-float-statement', ['canteen' => $canteen])
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @livewire('pocket-pay.list-canteen-sales', ['canteen' => $canteen])
            </div>
        </div>
    </div>
</x-app-layout>
