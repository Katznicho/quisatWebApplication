<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">MarzSMS</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            SMS provider for Quisat: one-time PINs, Pocket Pay card alerts to parents and other notifications.
                        </p>
                    </div>
                    @if ($settings->isReady())
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Enabled</span>
                    @elseif ($settings->hasCredentials())
                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">Configured, disabled</span>
                    @else
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">Not configured</span>
                    @endif
                </div>

                @foreach ([
                    'success' => 'border-green-300 bg-green-50 text-green-700',
                    'warning' => 'border-yellow-300 bg-yellow-50 text-yellow-800',
                    'error' => 'border-red-300 bg-red-50 text-red-700',
                ] as $flash => $classes)
                    @if (session($flash))
                        <div class="mt-4 rounded border px-4 py-3 {{ $classes }}">
                            {{ session($flash) }}
                        </div>
                    @endif
                @endforeach

                @if ($balance && ! $balance['success'])
                    <div class="mt-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-red-700">
                        Could not load balance: {{ $balance['message'] }}
                    </div>
                @elseif ($balance && $balance['balance'] < $settings->low_balance_threshold)
                    <div class="mt-4 rounded border border-yellow-300 bg-yellow-50 px-4 py-3 text-yellow-800">
                        MarzSMS balance is below UGX {{ number_format($settings->low_balance_threshold) }}. Top up to keep OTPs and parent alerts going out.
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-5">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Balance</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                            {{ $balance && $balance['success'] ? 'UGX '.number_format($balance['balance']) : '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Cost per SMS</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">
                            {{ $balance && $balance['success'] ? 'UGX '.number_format($balance['cost_per_sms'], 0) : '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Sent today</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['sent_today']) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Failed today</p>
                        <p class="mt-1 text-xl font-bold {{ $stats['failed_today'] > 0 ? 'text-red-600' : 'text-gray-800 dark:text-white' }}">{{ number_format($stats['failed_today']) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Spent this month</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">UGX {{ number_format($stats['spent_this_month']) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">API settings</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Use the API key and secret from your MarzSMS account (API Keys page). They are stored encrypted.
                    </p>

                    <form method="POST" action="{{ route('marzsms.settings.update') }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">API base URL</label>
                            <input type="url" name="base_url" required placeholder="https://your-marzsms-domain/api/v1"
                                value="{{ old('base_url', $settings->base_url) }}"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                            @error('base_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">API key</label>
                                <input type="text" name="api_key" autocomplete="off"
                                    placeholder="{{ $settings->maskedKey() ? 'Saved: '.$settings->maskedKey() : 'Enter API key' }}"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                @error('api_key')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">API secret</label>
                                <input type="password" name="api_secret" autocomplete="new-password"
                                    placeholder="{{ filled($settings->api_secret) ? 'Saved (leave blank to keep)' : 'Enter API secret' }}"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                @error('api_secret')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Low balance warning (UGX)</label>
                            <input type="number" min="0" name="low_balance_threshold"
                                value="{{ old('low_balance_threshold', $settings->low_balance_threshold) }}"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                            @error('low_balance_threshold')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="hidden" name="is_enabled" value="0">
                            <input type="checkbox" name="is_enabled" value="1" class="rounded border-gray-300"
                                @checked(old('is_enabled', $settings->is_enabled))>
                            Send SMS through MarzSMS
                        </label>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Save &amp; verify
                            </button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Top up balance</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            A mobile money prompt is sent to the number below. MarzSMS adds its service charge to the prompt.
                        </p>

                        <form method="POST" action="{{ route('marzsms.topups.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount (UGX)</label>
                                <input type="number" min="500" step="1" name="amount" required value="{{ old('amount') }}"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mobile money number</label>
                                <input type="tel" name="phone_number" required placeholder="07XXXXXXXX" value="{{ old('phone_number') }}"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                @error('phone_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-end">
                                <button type="submit" @disabled(! $settings->hasCredentials())
                                    class="w-full rounded bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    Send prompt
                                </button>
                            </div>
                        </form>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-xs uppercase tracking-wide text-gray-500">
                                        <th class="py-2 pr-3">Date</th>
                                        <th class="py-2 pr-3">Amount</th>
                                        <th class="py-2 pr-3">Phone</th>
                                        <th class="py-2 pr-3">Status</th>
                                        <th class="py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topups as $topup)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="py-2 pr-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $topup->created_at->format('M d, H:i') }}</td>
                                            <td class="py-2 pr-3 whitespace-nowrap text-gray-700 dark:text-gray-300">UGX {{ number_format($topup->amount) }}</td>
                                            <td class="py-2 pr-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $topup->phone_number }}</td>
                                            <td class="py-2 pr-3">
                                                <span @class([
                                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                    'bg-green-100 text-green-700' => $topup->status === 'completed',
                                                    'bg-red-100 text-red-700' => in_array($topup->status, ['failed', 'cancelled'], true),
                                                    'bg-yellow-100 text-yellow-700' => ! $topup->isFinal(),
                                                ]) title="{{ $topup->message }}">{{ ucfirst($topup->status) }}</span>
                                            </td>
                                            <td class="py-2 text-right">
                                                @unless ($topup->isFinal())
                                                    <form method="POST" action="{{ route('marzsms.topups.sync', $topup) }}">
                                                        @csrf
                                                        <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Refresh</button>
                                                    </form>
                                                @endunless
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-4 text-center text-gray-500">No top-ups yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Send test SMS</h3>
                        <form method="POST" action="{{ route('marzsms.test') }}" class="mt-4 space-y-4">
                            @csrf
                            <input type="tel" name="phone_number" required placeholder="07XXXXXXXX" value="{{ old('phone_number') }}"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                            <textarea name="message" rows="2" required maxlength="480"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">{{ old('message', 'Hello from Quisat. MarzSMS is set up.') }}</textarea>
                            <div class="flex justify-end">
                                <button type="submit" @disabled(! $settings->isReady())
                                    class="rounded bg-gray-800 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-50">
                                    Send test
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Messages sent</h3>
                @livewire('marz-sms.list-sms-messages')
            </div>
        </div>
    </div>
</x-app-layout>
