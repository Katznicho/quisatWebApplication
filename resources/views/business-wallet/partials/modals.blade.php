{{-- Setup PIN --}}
<div id="setupPinModal" class="hidden fixed inset-0 z-50 bg-gray-600 bg-opacity-50 overflow-y-auto">
    <div class="relative top-20 mx-auto w-full max-w-md rounded-md border bg-white p-6 shadow-lg">
        <h3 class="text-lg font-semibold mb-4">Set Up Withdrawal PIN</h3>
        <form method="POST" action="{{ route('business.wallet.setup-pin') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">PIN (4-6 digits)</label>
                <input type="password" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm PIN</label>
                <input type="password" name="pin_confirmation" inputmode="numeric" pattern="\d{4,6}" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('setupPinModal').classList.add('hidden')"
                    class="rounded border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save PIN</button>
            </div>
        </form>
    </div>
</div>

{{-- Change PIN --}}
<div id="changePinModal" class="hidden fixed inset-0 z-50 bg-gray-600 bg-opacity-50 overflow-y-auto">
    <div class="relative top-20 mx-auto w-full max-w-md rounded-md border bg-white p-6 shadow-lg">
        <h3 class="text-lg font-semibold mb-4">Change Withdrawal PIN</h3>
        <form method="POST" action="{{ route('business.wallet.change-pin') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Current PIN</label>
                <input type="password" name="current_pin" inputmode="numeric" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">New PIN</label>
                <input type="password" name="pin" inputmode="numeric" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm New PIN</label>
                <input type="password" name="pin_confirmation" inputmode="numeric" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('changePinModal').classList.add('hidden')"
                    class="rounded border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Update PIN</button>
            </div>
        </form>
    </div>
</div>

{{-- Reset PIN --}}
<div id="resetPinModal" class="hidden fixed inset-0 z-50 bg-gray-600 bg-opacity-50 overflow-y-auto">
    <div class="relative top-20 mx-auto w-full max-w-md rounded-md border bg-white p-6 shadow-lg">
        <h3 class="text-lg font-semibold mb-4">Reset Withdrawal PIN</h3>
        <p class="text-sm text-gray-600 mb-4">Enter your account login password to reset your withdrawal PIN.</p>
        <form method="POST" action="{{ route('business.wallet.reset-pin') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Account Password</label>
                <input type="password" name="password" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">New PIN</label>
                <input type="password" name="pin" inputmode="numeric" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm New PIN</label>
                <input type="password" name="pin_confirmation" inputmode="numeric" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('resetPinModal').classList.add('hidden')"
                    class="rounded border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Reset PIN</button>
            </div>
        </form>
    </div>
</div>

{{-- Withdraw --}}
<div id="withdrawModal" class="hidden fixed inset-0 z-50 bg-gray-600 bg-opacity-50 overflow-y-auto">
    <div class="relative top-20 mx-auto w-full max-w-md rounded-md border bg-white p-6 shadow-lg">
        <h3 class="text-lg font-semibold mb-4">Withdraw to Mobile Money</h3>
        <p class="text-sm text-gray-600 mb-4">Funds are sent instantly to your mobile money number when you have sufficient available balance.</p>
        <form method="POST" action="{{ route('business.wallet.withdraw') }}" class="space-y-4" id="withdrawForm">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Amount to receive (UGX)</label>
                <input type="number" name="amount" id="withdrawAmount" min="500" step="1" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1" id="feeEstimate">Fee: — · Total debited: —</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mobile Money Number</label>
                <input type="text" name="phone_number" value="{{ $business->phone }}" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Withdrawal PIN</label>
                <input type="password" name="pin" inputmode="numeric" maxlength="6" required
                    class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                <textarea name="notes" rows="2" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('withdrawModal').classList.add('hidden')"
                    class="rounded border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white">Withdraw now</button>
            </div>
        </form>
    </div>
</div>

<script>
    const withdrawAmountInput = document.getElementById('withdrawAmount');
    const feeEstimate = document.getElementById('feeEstimate');

    if (withdrawAmountInput) {
        withdrawAmountInput.addEventListener('input', async function () {
            const amount = this.value;
            if (!amount || amount < 500) {
                feeEstimate.textContent = 'Fee: — · Total debited: —';
                return;
            }
            try {
                const response = await fetch('{{ route('business.wallet.estimate-fee') }}?amount=' + encodeURIComponent(amount), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                feeEstimate.textContent = `Fee: UGX ${Number(data.fee).toLocaleString()} · Total debited: UGX ${Number(data.total).toLocaleString()}`;
            } catch (e) {
                feeEstimate.textContent = 'Could not estimate fee.';
            }
        });
    }
</script>
