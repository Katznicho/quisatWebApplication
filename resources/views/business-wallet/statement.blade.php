<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Account Statement</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            View, download, or email your wallet transaction history.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('business.wallet.index') }}"
                            class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Back to Wallet
                        </a>
                        <a href="{{ route('business.statement.download', ['from' => $from, 'to' => $to]) }}"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Download PDF
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('business.statement.index') }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-8 p-4 rounded-lg bg-gray-50 border border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                        <input type="date" name="from" value="{{ $from }}"
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input type="date" name="to" value="{{ $to }}"
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit"
                            class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Generate Statement
                        </button>
                        <a href="{{ route('business.statement.index') }}"
                            class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-white">
                            Last 30 Days
                        </a>
                    </div>
                </form>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-6 py-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <p class="text-blue-100 text-sm uppercase tracking-wide">Account Statement</p>
                                <h3 class="text-2xl font-bold mt-1">{{ $statement['business']->name }}</h3>
                                <p class="text-blue-100 text-sm mt-1">Account {{ $statement['business']->account_number }}</p>
                            </div>
                            <div class="text-sm text-blue-100 md:text-right">
                                <p><span class="font-semibold text-white">Statement No:</span> {{ $statement['statement_number'] }}</p>
                                <p><span class="font-semibold text-white">Period:</span> {{ $statement['from']->format('M j, Y') }} – {{ $statement['to']->format('M j, Y') }}</p>
                                <p><span class="font-semibold text-white">Generated:</span> {{ $statement['generated_at']->format('M j, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0 border-b border-gray-200">
                        <div class="p-5 border-r border-gray-200">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Opening Balance</p>
                            <p class="text-xl font-bold text-gray-900 mt-1">{{ $statement['currency'] }} {{ number_format($statement['opening_balance'], 0) }}</p>
                        </div>
                        <div class="p-5 border-r border-gray-200">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Total Credits</p>
                            <p class="text-xl font-bold text-green-600 mt-1">{{ $statement['currency'] }} {{ number_format($statement['total_credits'], 0) }}</p>
                        </div>
                        <div class="p-5 border-r border-gray-200">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Debits & Fees</p>
                            <p class="text-xl font-bold text-red-600 mt-1">{{ $statement['currency'] }} {{ number_format($statement['total_debits'] + $statement['total_fees'], 0) }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Closing Balance</p>
                            <p class="text-xl font-bold text-blue-700 mt-1">{{ $statement['currency'] }} {{ number_format($statement['closing_balance'], 0) }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-900 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Date</th>
                                    <th class="px-4 py-3 text-left font-semibold">Reference</th>
                                    <th class="px-4 py-3 text-left font-semibold">Description</th>
                                    <th class="px-4 py-3 text-left font-semibold">Type</th>
                                    <th class="px-4 py-3 text-right font-semibold">Credit</th>
                                    <th class="px-4 py-3 text-right font-semibold">Debit</th>
                                    <th class="px-4 py-3 text-right font-semibold">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statement['lines'] as $index => $line)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} border-t border-gray-200">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div>{{ $line['date']->format('M j, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $line['date']->format('H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600 max-w-[140px] break-all">{{ $line['reference'] }}</td>
                                        <td class="px-4 py-3">{{ $line['description'] }}</td>
                                        <td class="px-4 py-3">{{ $line['type'] }}</td>
                                        <td class="px-4 py-3 text-right text-green-600 font-medium">
                                            {{ $line['credit'] !== null ? $statement['currency'].' '.number_format($line['credit'], 0) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-red-600 font-medium">
                                            {{ $line['debit'] !== null ? $statement['currency'].' '.number_format($line['debit'], 0) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold">
                                            {{ $statement['currency'] }} {{ number_format($line['balance_after'], 0) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                            No transactions found for the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-xs text-gray-500">
                        Computer-generated statement from {{ config('app.name') }}.
                        Lifetime total received: {{ $statement['currency'] }} {{ number_format($statement['total_balance'], 0) }}
                        · Current available: {{ $statement['currency'] }} {{ number_format($statement['available_balance'], 0) }}
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Email Statement</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Send this statement as a PDF attachment to any email address.
                </p>
                <form method="POST" action="{{ route('business.statement.email') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                        <input type="email" name="email" value="{{ old('email', $business->email) }}" required
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                        @error('email')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message (optional)</label>
                        <textarea name="message" rows="3" placeholder="Add a short note to include in the email..."
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
                    </div>
                    <div>
                        <button type="submit"
                            class="rounded bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            Send Statement by Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
