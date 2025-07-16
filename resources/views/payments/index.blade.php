<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 px-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Your Payment History</h2>

        <div class="overflow-x-auto bg-white rounded-xl shadow p-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="text-left text-sm text-gray-600 uppercase tracking-wider">
                        <th class="py-2 px-4">Plan</th>
                        <th class="py-2 px-4">Amount</th>
                        <th class="py-2 px-4">Status</th>
                        <th class="py-2 px-4">Method</th>
                        <th class="py-2 px-4">Reference</th>
                        <th class="py-2 px-4">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="py-3 px-4">{{ $payment->subscriptionPlan->name ?? 'N/A' }}</td>
                            <td class="py-3 px-4">${{ number_format($payment->amount, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded-full text-white text-xs font-medium 
                                    {{ $payment->status === 'success' ? 'bg-green-500' : 'bg-red-500' }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">{{ ucfirst($payment->payment_method) }}</td>
                            <td class="py-3 px-4 text-xs">{{ $payment->payment_reference }}</td>
                            <td class="py-3 px-4">{{ \Carbon\Carbon::parse($payment->paid_at)->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 px-4 text-center text-gray-500">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
