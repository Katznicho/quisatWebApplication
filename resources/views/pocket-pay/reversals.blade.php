<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @include('pocket-pay.partials.nav')
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Attendants cannot reverse a sale themselves. Approving returns the money to the student card and the canteen float; rejecting keeps the sale.</p>
                @livewire('pocket-pay.list-canteen-sales', ['reversalsOnly' => true])
            </div>
        </div>
    </div>
</x-app-layout>
