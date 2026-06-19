<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Default Withdrawal Fee Tiers</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        These tiers apply to all businesses unless a business enables custom withdrawal tiers.
                    </p>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('withdrawal.settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

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

                    <button type="button" onclick="addTierRow()" class="text-sm font-semibold text-blue-600">+ Add tier</button>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Save Default Tiers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function addTierRow() {
            const tbody = document.querySelector('#tiersTable tbody');
            const index = tbody.querySelectorAll('tr').length;
            const row = document.createElement('tr');
            row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-2"><input type="number" name="tiers[${index}][min_amount]" min="0" required class="w-full rounded border border-gray-300 px-2 py-1"></td>
                <td class="px-4 py-2"><input type="number" name="tiers[${index}][max_amount]" class="w-full rounded border border-gray-300 px-2 py-1" placeholder="Empty = above min"></td>
                <td class="px-4 py-2"><input type="number" name="tiers[${index}][charge_amount]" min="0" required class="w-full rounded border border-gray-300 px-2 py-1"></td>
                <td class="px-4 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 text-xs">Remove</button></td>
            `;
            tbody.appendChild(row);
        }
    </script>
</x-app-layout>
