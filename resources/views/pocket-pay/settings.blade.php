<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                @include('pocket-pay.partials.nav')
            </div>

            @if (session('success'))
                <div class="rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('pocket-pay.settings.update') }}" class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                @csrf
                @method('PUT')
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Marz Pay account</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pocket Pay collections and withdrawals use this account. SMS and charges stay on the Marz accounts already used by the rest of Quisat. Paste the webhook signing secret if web signing is on for this Marz account.</p>
                    </div>
                    <span @class([
                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-50 text-emerald-800' => $settings->hasPayCredentials(),
                        'bg-gray-100 text-gray-600' => ! $settings->hasPayCredentials(),
                    ])>{{ $settings->hasPayCredentials() ? 'Ready' : 'Not set' }}</span>
                </div>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Base URL</label>
                        <input type="url" name="marzpay_base_url" value="{{ old('marzpay_base_url', $settings->marzpay_base_url) }}" placeholder="https://wallet.wearemarz.com/api/v1" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                        @error('marzpay_base_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">API key</label>
                            <input type="password" name="marzpay_api_key" autocomplete="new-password" placeholder="{{ $settings->maskedPayKey() ?: 'Paste the API key' }}" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                            @error('marzpay_api_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">API secret</label>
                            <input type="password" name="marzpay_api_secret" autocomplete="new-password" placeholder="{{ $settings->marzpay_api_secret ? 'Secret saved' : 'Paste the API secret' }}" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                            @error('marzpay_api_secret') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook signing secret</label>
                        <input type="password" name="marzpay_webhook_secret" autocomplete="new-password" placeholder="{{ $settings->hasWebhookSecret() ? 'Signing secret saved' : 'Paste the signing secret' }}" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                        <p class="mt-1 text-xs text-gray-500">Marz Pay → Business Settings → Webhooks & Security → Sign outgoing webhooks → Reveal. Leave blank to keep the saved secret.</p>
                        @error('marzpay_webhook_secret') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="clear_marzpay" value="1" class="rounded border-gray-300">
                            Remove the saved key and secret
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="clear_webhook_secret" value="1" class="rounded border-gray-300">
                            Remove the signing secret
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save account</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
