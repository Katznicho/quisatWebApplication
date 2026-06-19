<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded border border-red-300 bg-red-50 px-4 py-3 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Business Wallet</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Manage your online payment balance and withdraw funds to mobile money.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('business.statement.index') }}"
                            class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Account Statement
                        </a>
                        @if (! $business->hasWithdrawalPin())
                            <button type="button" onclick="document.getElementById('setupPinModal').classList.remove('hidden')"
                                class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Set Up Withdrawal PIN
                            </button>
                        @else
                            <button type="button" onclick="document.getElementById('changePinModal').classList.remove('hidden')"
                                class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Change PIN
                            </button>
                            <button type="button" onclick="document.getElementById('resetPinModal').classList.remove('hidden')"
                                class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Forgot PIN?
                            </button>
                            <button type="button" onclick="document.getElementById('withdrawModal').classList.remove('hidden')"
                                class="rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                Request Withdrawal
                            </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-5">
                        <p class="text-sm font-medium text-blue-700">Available Balance</p>
                        <p class="text-3xl font-bold text-blue-900 mt-1">
                            UGX {{ number_format($business->available_balance, 0) }}
                        </p>
                        <p class="text-xs text-blue-600 mt-2">Ready to withdraw</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                        <p class="text-sm font-medium text-amber-700">Held Balance</p>
                        <p class="text-3xl font-bold text-amber-900 mt-1">
                            UGX {{ number_format($business->held_balance ?? 0, 0) }}
                        </p>
                        <p class="text-xs text-amber-600 mt-2">Paid online, pending order delivery confirmation</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <p class="text-sm font-medium text-gray-700">Total Balance</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            UGX {{ number_format($business->total_balance, 0) }}
                        </p>
                        <p class="text-xs text-gray-600 mt-2">Lifetime online payments received</p>
                    </div>
                </div>

                @if (! $business->hasWithdrawalPin())
                    <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-800 mb-6">
                        Set up a withdrawal PIN before you can withdraw funds from your wallet.
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Withdrawal Fee Tiers</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Standard platform fees apply to every withdrawal. These are set by Quisat and are the same for all businesses.
                        </p>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-900 text-white">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Range (UGX)</th>
                                        <th class="px-4 py-2 text-left">Charge (UGX)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tiers as $index => $tier)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                            <td class="px-4 py-2">{{ $tier->rangeLabel() }}</td>
                                            <td class="px-4 py-2 font-semibold">{{ number_format($tier->charge_amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Recent Withdrawals</h3>
                        <div class="space-y-2">
                            @forelse ($withdrawals as $withdrawal)
                                <div class="rounded border border-gray-200 p-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="font-semibold">UGX {{ number_format($withdrawal->amount, 0) }}</span>
                                        <span class="capitalize text-gray-600">{{ $withdrawal->status }}</span>
                                    </div>
                                    <p class="text-gray-500 mt-1">
                                        Fee: UGX {{ number_format($withdrawal->fee_amount, 0) }} ·
                                        {{ $withdrawal->created_at->format('M j, Y H:i') }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No withdrawal requests yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Recent Transactions</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Date</th>
                                <th class="px-4 py-2 text-left">Type</th>
                                <th class="px-4 py-2 text-left">Description</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2 text-right">Available After</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledgers as $ledger)
                                <tr class="border-t border-gray-200">
                                    <td class="px-4 py-2">{{ $ledger->created_at->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $ledger->type) }}</td>
                                    <td class="px-4 py-2">{{ $ledger->description }}</td>
                                    <td class="px-4 py-2 text-right {{ $ledger->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $ledger->type === 'credit' ? '+' : '-' }}UGX {{ number_format($ledger->amount, 0) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">UGX {{ number_format($ledger->available_balance_after, 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('business-wallet.partials.modals', ['business' => $business])
</x-app-layout>
