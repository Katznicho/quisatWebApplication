<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Withdrawal Requests</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Review and process business withdrawal requests.
                    </p>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Date</th>
                                <th class="px-4 py-2 text-left">Business</th>
                                <th class="px-4 py-2 text-left">Phone</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2 text-right">Fee</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withdrawals as $withdrawal)
                                <tr class="border-t border-gray-200 align-top">
                                    <td class="px-4 py-3">{{ $withdrawal->created_at->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-3">{{ $withdrawal->business?->name }}</td>
                                    <td class="px-4 py-3">{{ $withdrawal->phone_number }}</td>
                                    <td class="px-4 py-3 text-right">UGX {{ number_format($withdrawal->amount, 0) }}</td>
                                    <td class="px-4 py-3 text-right">UGX {{ number_format($withdrawal->fee_amount, 0) }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $withdrawal->status }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('withdrawal.requests.update', $withdrawal) }}" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                                @foreach (['pending', 'processing', 'completed', 'failed', 'cancelled'] as $status)
                                                    <option value="{{ $status }}" @selected($withdrawal->status === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <textarea name="admin_notes" rows="2" placeholder="Admin notes"
                                                class="w-full rounded border border-gray-300 px-2 py-1 text-xs">{{ $withdrawal->admin_notes }}</textarea>
                                            <button type="submit" class="rounded bg-blue-600 px-2 py-1 text-xs text-white">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No withdrawal requests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $withdrawals->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
