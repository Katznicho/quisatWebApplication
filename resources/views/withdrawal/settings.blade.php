<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Withdrawal Fee Tiers</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Mobile money collections withdraw to phone. Card collections can only be pushed to bank via MarzPay bank transfer.
                    </p>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('withdrawal.settings.update') }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Mobile Money Withdrawal Fees</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full text-sm" id="tiersTable">
                                <thead class="bg-gray-900 text-white">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Min Amount (UGX)</th>
                                        <th class="px-4 py-2 text-left">Max Amount (UGX)</th>
                                        <th class="px-4 py-2 text-left">Charge (UGX)</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tiers as $index => $tier)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                            <td class="px-4 py-2">
                                                <input type="number" name="tiers[{{ $index }}][min_amount]" value="{{ $tier->min_amount }}" min="0" required
                                                    class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="tiers[{{ $index }}][max_amount]" value="{{ $tier->max_amount }}"
                                                    class="w-full rounded border border-gray-300 px-2 py-1" placeholder="Empty = above min">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="tiers[{{ $index }}][charge_amount]" value="{{ $tier->charge_amount }}" min="0" required
                                                    class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-4 py-2">
                                                <button type="button" onclick="this.closest('tr').remove()" class="text-red-600 text-xs">Remove</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addTierRow('tiersTable', 'tiers')" class="mt-2 text-sm font-semibold text-blue-600">+ Add mobile money tier</button>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Card → Bank Transfer Fees</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Align these with MarzPay wallet-to-bank pricing. Recipient receives the transfer amount; Quisat/MarzPay charge is on top.
                        </p>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full text-sm" id="bankTiersTable">
                                <thead class="bg-indigo-900 text-white">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Min Amount (UGX)</th>
                                        <th class="px-4 py-2 text-left">Max Amount (UGX)</th>
                                        <th class="px-4 py-2 text-left">Charge (UGX)</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bankTiers as $index => $tier)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                            <td class="px-4 py-2">
                                                <input type="number" name="bank_tiers[{{ $index }}][min_amount]" value="{{ $tier->min_amount }}" min="0" required
                                                    class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="bank_tiers[{{ $index }}][max_amount]" value="{{ $tier->max_amount }}"
                                                    class="w-full rounded border border-gray-300 px-2 py-1" placeholder="Empty = above min">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="bank_tiers[{{ $index }}][charge_amount]" value="{{ $tier->charge_amount }}" min="0" required
                                                    class="w-full rounded border border-gray-300 px-2 py-1">
                                            </td>
                                            <td class="px-4 py-2">
                                                <button type="button" onclick="this.closest('tr').remove()" class="text-red-600 text-xs">Remove</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addTierRow('bankTiersTable', 'bank_tiers')" class="mt-2 text-sm font-semibold text-indigo-600">+ Add bank transfer tier</button>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Save Fee Tiers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function addTierRow(tableId, fieldPrefix) {
            const tbody = document.querySelector('#' + tableId + ' tbody');
            const index = tbody.querySelectorAll('tr').length;
            const row = document.createElement('tr');
            row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-2"><input type="number" name="${fieldPrefix}[${index}][min_amount]" min="0" required class="w-full rounded border border-gray-300 px-2 py-1"></td>
                <td class="px-4 py-2"><input type="number" name="${fieldPrefix}[${index}][max_amount]" class="w-full rounded border border-gray-300 px-2 py-1" placeholder="Empty = above min"></td>
                <td class="px-4 py-2"><input type="number" name="${fieldPrefix}[${index}][charge_amount]" min="0" required class="w-full rounded border border-gray-300 px-2 py-1"></td>
                <td class="px-4 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 text-xs">Remove</button></td>
            `;
            tbody.appendChild(row);
        }
    </script>
</x-app-layout>
