<div class="space-y-4">
    <div class="grid grid-cols-3 gap-3 text-sm">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500">Balance</p>
            <p class="mt-1 font-bold text-gray-800 dark:text-white">UGX {{ number_format($card->balance) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500">Spent today</p>
            <p class="mt-1 font-bold text-gray-800 dark:text-white">UGX {{ number_format($spentToday) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500">Daily limit</p>
            <p class="mt-1 font-bold text-gray-800 dark:text-white">{{ $card->daily_limit !== null ? 'UGX '.number_format($card->daily_limit) : 'None' }}</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-xs uppercase tracking-wide text-gray-500">
                    <th class="py-2 pr-3">Date</th>
                    <th class="py-2 pr-3">Details</th>
                    <th class="py-2 pr-3 text-right">Amount</th>
                    <th class="py-2 text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <td class="py-2 pr-3 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $transaction->created_at->format('M d, H:i') }}</td>
                        <td class="py-2 pr-3 text-gray-800 dark:text-gray-200">{{ $transaction->description }}</td>
                        <td @class([
                            'py-2 pr-3 text-right whitespace-nowrap font-medium',
                            'text-green-600' => $transaction->direction === 'credit',
                            'text-red-600' => $transaction->direction === 'debit',
                        ])>{{ $transaction->direction === 'credit' ? '+' : '−' }}{{ number_format($transaction->amount) }}</td>
                        <td class="py-2 text-right whitespace-nowrap text-gray-800 dark:text-gray-200">{{ number_format($transaction->balance_after) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-500">No transactions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($transactions->count() === 50)
            <p class="mt-2 text-xs text-gray-500">Showing the latest 50 transactions.</p>
        @endif
    </div>
</div>
