<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">MarzPay Settings</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Configure the extra Quisat charge added on top of the product or event amount when a customer pays via MarzPay.
                        The total (base + charge) is what appears on the mobile money prompt or card checkout.
                    </p>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('marzpay.settings.update') }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Mobile Money Charge</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Charge type</label>
                                <select name="mobile_money_charge_type"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                    <option value="fixed" @selected(old('mobile_money_charge_type', $settings->mobile_money_charge_type) === 'fixed')>Fixed amount (UGX)</option>
                                    <option value="percent" @selected(old('mobile_money_charge_type', $settings->mobile_money_charge_type) === 'percent')>Percentage (%)</option>
                                </select>
                                @error('mobile_money_charge_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Charge value</label>
                                <input type="number" step="0.01" min="0" name="mobile_money_charge_value"
                                    value="{{ old('mobile_money_charge_value', $settings->mobile_money_charge_value) }}"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                @error('mobile_money_charge_value')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Example: fixed 500 means a 10,000 UGX order prompts for 10,500 UGX on MTN/Airtel.</p>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Card Payment Charge</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Charge type</label>
                                <select name="card_charge_type"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                    <option value="fixed" @selected(old('card_charge_type', $settings->card_charge_type) === 'fixed')>Fixed amount (UGX)</option>
                                    <option value="percent" @selected(old('card_charge_type', $settings->card_charge_type) === 'percent')>Percentage (%)</option>
                                </select>
                                @error('card_charge_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Charge value</label>
                                <input type="number" step="0.01" min="0" name="card_charge_value"
                                    value="{{ old('card_charge_value', $settings->card_charge_value) }}"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                @error('card_charge_value')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
