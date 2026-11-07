<x-app-layout>
    @php
        $money = fn ($amount) => 'UGX '.number_format((float) $amount);
        $held = $cardTotal + $floatTotal;
        $field = 'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
    @endphp
    <x-pocket-pay.shell>
        @unless ($gateway->hasPayCredentials())
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <p>Collections and withdrawals stay off until the Pocket Pay Marz Pay account is saved.</p>
                <a href="{{ route('pocket-pay.settings.edit') }}" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white">Add Marz Pay account</a>
            </div>
        @endunless

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Held now</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums dark:text-white">{{ $money($held) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ number_format($schools->count()) }} {{ \Illuminate\Support\Str::plural('school', $schools->count()) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">On student cards</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums dark:text-white">{{ $money($cardTotal) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ number_format($activeCards) }} active {{ \Illuminate\Support\Str::plural('card', $activeCards) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">At canteens</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums dark:text-white">{{ $money($floatTotal) }}</p>
                <p class="mt-2 text-sm text-slate-500">Available for sales</p>
            </div>
        </section>

        <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Marz Pay wallet</p>
                <div class="mt-2">
                    @include('pocket-pay.partials.marz-balance', ['refreshUrl' => route('pocket-pay.overview', ['refresh_balance' => 1])])
                </div>
            </div>
            <a href="{{ route('pocket-pay.reports') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:text-white">Money reports</a>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">Schools</h2>
                <p class="mt-1 text-sm text-slate-500">Open a school to see its cards, canteens, sales, and top-ups.</p>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($schools as $row)
                    @php
                        $share = $held > 0 ? round(($row->total / $held) * 100, 1) : 0;
                        $name = $row->business?->name ?? 'School';
                    @endphp
                    @if ($row->business)
                        <a href="{{ route('pocket-pay.schools.show', $row->business) }}" class="flex items-center justify-between gap-6 px-6 py-5 hover:bg-slate-50 dark:hover:bg-slate-900/40">
                    @else
                        <div class="flex items-center justify-between gap-6 px-6 py-5">
                    @endif
                        <div>
                            <p class="text-base font-semibold text-slate-950 dark:text-white">{{ $name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ number_format($row->active_cards) }} active {{ \Illuminate\Support\Str::plural('card', $row->active_cards) }} · {{ number_format($row->canteens) }} {{ \Illuminate\Support\Str::plural('canteen', $row->canteens) }} · {{ number_format($share, 1) }}% of money held</p>
                        </div>
                        <p class="shrink-0 text-lg font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($row->total) }}</p>
                    @if ($row->business)
                        </a>
                    @else
                        </div>
                    @endif
                @empty
                    <p class="px-6 py-14 text-center text-sm text-slate-500">No school has a Pocket Pay card or canteen yet.</p>
                @endforelse
            </div>
        </section>

        <details class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <summary class="cursor-pointer list-none px-6 py-5 text-base font-semibold text-slate-950 dark:text-white">Withdraw from the Marz Pay wallet</summary>
            <div class="space-y-6 border-t border-slate-100 px-6 py-6 dark:border-slate-800">
                <p class="text-sm leading-6 text-slate-500">This leaves the Pocket Pay Marz Pay account. Card balances and canteen floats stay as they are.</p>
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <form method="POST" action="{{ route('pocket-pay.withdrawals.store') }}" class="space-y-3">
                        @csrf
                        <p class="text-sm font-semibold text-slate-950 dark:text-white">Mobile money</p>
                        <label class="block text-xs font-medium text-slate-500">Amount (UGX)
                            <input type="number" name="amount" min="500" step="1" required value="{{ old('phone_number') ? old('amount') : '' }}" placeholder="e.g. 50000" class="{{ $field }} mt-1">
                        </label>
                        <label class="block text-xs font-medium text-slate-500">Mobile money number
                            <input type="text" name="phone_number" required value="{{ old('phone_number') }}" placeholder="e.g. 0772 123 456" class="{{ $field }} mt-1">
                        </label>
                        @error('phone_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <label class="block text-xs font-medium text-slate-500">Note
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Weekly withdrawal" class="{{ $field }} mt-1">
                        </label>
                        <button class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled(! $gateway->hasPayCredentials())>Withdraw</button>
                    </form>
                    <form method="POST" action="{{ route('pocket-pay.withdrawals.bank') }}" class="space-y-3">
                        @csrf
                        <p class="text-sm font-semibold text-slate-950 dark:text-white">Bank</p>
                        <label class="block text-xs font-medium text-slate-500">Amount (UGX)
                            <input type="number" name="amount" min="500" step="1" required value="{{ old('bank_account_number') ? old('amount') : '' }}" placeholder="e.g. 500000" class="{{ $field }} mt-1">
                        </label>
                        <label class="block text-xs font-medium text-slate-500">Bank name
                            <input type="text" name="bank_name" required value="{{ old('bank_name') }}" placeholder="e.g. Stanbic" class="{{ $field }} mt-1">
                        </label>
                        <label class="block text-xs font-medium text-slate-500">Account number
                            <input type="text" name="bank_account_number" required value="{{ old('bank_account_number') }}" placeholder="e.g. 9030012345678" class="{{ $field }} mt-1">
                        </label>
                        <label class="block text-xs font-medium text-slate-500">Account name
                            <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" placeholder="e.g. Quisat Pocket Pay" class="{{ $field }} mt-1">
                        </label>
                        @error('bank_account_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button class="inline-flex h-10 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-400 dark:border-slate-700 dark:text-white" @disabled(! $gateway->hasPayCredentials())>Withdraw to bank</button>
                    </form>
                </div>

                @if ($withdrawals->isNotEmpty())
                    <div class="overflow-x-auto border-t border-slate-100 pt-5 dark:border-slate-800">
                        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Recent withdrawals</h3>
                        <table class="mt-3 min-w-full text-sm">
                            <thead class="text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="py-3 pr-4 font-semibold">When</th>
                                    <th class="px-4 py-3 font-semibold">To</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                    <th class="py-3 pl-4 text-right font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($withdrawals as $withdrawal)
                                    <tr class="border-b border-slate-50 last:border-0 dark:border-slate-800">
                                        <td class="py-3 pr-4 text-slate-600">{{ $withdrawal->created_at->timezone(config('app.timezone'))->format('j M Y, g:i A') }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-950 dark:text-white">{{ $withdrawal->method === 'bank' ? trim($withdrawal->bank_name.' · '.$withdrawal->bank_account_number) : $withdrawal->phone_number }}</td>
                                        <td class="px-4 py-3">{{ ucfirst($withdrawal->status) }}</td>
                                        <td class="py-3 pl-4 text-right font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($withdrawal->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </details>
    </x-pocket-pay.shell>
</x-app-layout>
