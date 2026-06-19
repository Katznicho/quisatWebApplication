<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">MarzPay Transactions</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        All mobile money and card collections processed through MarzPay.
                        Use <strong>Refresh status</strong> to manually sync a transaction from MarzPay when webhooks are delayed.
                    </p>
                </div>

                @livewire('marz-pay.list-payment-collections')
            </div>
        </div>
    </div>
</x-app-layout>
