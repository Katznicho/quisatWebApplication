<x-app-layout>
    <div class="py-12" x-data="{ showModal: false }" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (isset($business))
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">My Wallet</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $business->name }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('business.wallet.index') }}"
                            class="rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            Manage Wallet
                        </a>
                        <a href="{{ route('business.statement.index') }}"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Account Statement
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm font-medium text-blue-700">Available Balance</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1">UGX {{ number_format($business->available_balance ?? 0, 0) }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-medium text-amber-700">Held Balance</p>
                        <p class="text-2xl font-bold text-amber-900 mt-1">UGX {{ number_format($business->held_balance ?? 0, 0) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-medium text-gray-700">Total Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">UGX {{ number_format($business->total_balance ?? 0, 0) }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manage Businesses</h2>

                </div>

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show"
                        class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 transition"
                        role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <button @click="show = false"
                            class="absolute top-1 right-2 text-xl font-semibold text-green-700">
                            &times;
                        </button>
                    </div>
                @endif


                @livewire('list-business')
            </div>
        </div>
 
    </div>
</x-app-layout>
