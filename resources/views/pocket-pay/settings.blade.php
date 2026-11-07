<x-app-layout>
    @php
        $field = 'h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
    @endphp
    <x-pocket-pay.shell>
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('pocket-pay.settings.update') }}" class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">API account</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Collections and withdrawals use this Marz Pay account. SMS and the charge rate stay on the accounts the rest of Quisat already uses.</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-800' => $settings->hasPayCredentials(),
                            'bg-amber-100 text-amber-900' => ! $settings->hasPayCredentials(),
                        ])>{{ $settings->hasPayCredentials() ? 'Ready' : 'Not set' }}</span>
                    </div>
                    <div class="mt-6 space-y-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Base URL
                            <input type="url" name="marzpay_base_url" value="{{ old('marzpay_base_url', $settings->payBaseUrl()) }}" class="{{ $field }} mt-1.5 font-normal">
                        </label>
                        @error('marzpay_base_url') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">API key
                                <input type="text" name="pp_key" autocomplete="off" spellcheck="false" value="{{ old('pp_key', old('marzpay_api_key')) }}" placeholder="{{ $settings->maskedPayKey() ?: 'Paste the API key' }}" class="{{ $field }} mt-1.5 font-normal">
                            </label>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">API secret
                                <input type="text" name="pp_token" autocomplete="off" spellcheck="false" value="{{ old('pp_token', old('marzpay_api_secret')) }}" placeholder="Paste the API secret" class="{{ $field }} mt-1.5 font-normal">
                            </label>
                        </div>
                        @error('marzpay_api_key') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        @error('marzpay_api_secret') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->credentialsUnreadable())
                            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">A key was saved but could not be read. Paste the API key and secret again.</p>
                        @elseif ($settings->maskedPayKey())
                            <p class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">Saved API key <span class="font-mono">{{ $settings->maskedPayKey() }}</span>. Saving again replaces this key and the secret.</p>
                        @else
                            <p class="text-xs leading-5 text-slate-400">Paste the API key and API secret from the Pocket Pay Marz account. Saving stores both.</p>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">Webhook signing</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Paste the signing secret after web signing is turned on for this Marz account. Pocket Pay callbacks are checked against it. School-fee callbacks are left as they are.</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-800' => $settings->hasWebhookSecret(),
                            'bg-slate-100 text-slate-600' => ! $settings->hasWebhookSecret(),
                        ])>{{ $settings->hasWebhookSecret() ? 'Checking signatures' : 'Optional' }}</span>
                    </div>
                    <div class="mt-6 space-y-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Signing secret
                            <input type="text" name="pp_sign" autocomplete="off" spellcheck="false" value="{{ old('pp_sign', old('marzpay_webhook_secret')) }}" placeholder="Paste the signing secret" class="{{ $field }} mt-1.5 font-normal">
                        </label>
                        @error('marzpay_webhook_secret') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <p class="text-xs leading-5 text-slate-400">Marz Pay → Business Settings → Webhooks & Security → Sign outgoing webhooks → Reveal. Paste it and save to store it.</p>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white hover:bg-slate-800">Save account</button>
                </div>
            </div>

            <aside class="h-fit space-y-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-600 shadow-sm dark:border-slate-800 dark:bg-gray-900 dark:text-slate-300">
                <div>
                    <h2 class="font-semibold text-slate-950 dark:text-white">Wallet on Marz Pay</h2>
                    <div class="mt-3">
                        @include('pocket-pay.partials.marz-balance', ['refreshUrl' => route('pocket-pay.settings.edit', ['refresh_balance' => 1])])
                    </div>
                </div>
                <h2 class="font-semibold text-slate-950 dark:text-white">What this account does</h2>
                <ul class="mt-3 space-y-3">
                    <li>Parent and canteen mobile-money top-ups are collected here.</li>
                    <li>Withdrawals on the overview are sent from here.</li>
                    <li>Student card balances and canteen floats are Quisat records. They are not the Marz Pay wallet balance.</li>
                    <li>Once a signing secret is saved, a Pocket Pay callback with a bad signature is rejected.</li>
                </ul>
            </aside>
        </form>
    </x-pocket-pay.shell>
</x-app-layout>
