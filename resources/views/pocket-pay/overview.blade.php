<x-app-layout>
    @php
        $money = fn ($amount) => 'UGX '.number_format((float) $amount);
        $held = $cardTotal + $floatTotal;
        $cardShare = $held > 0 ? round(($cardTotal / $held) * 100, 1) : 0;
        $floatShare = $held > 0 ? round(($floatTotal / $held) * 100, 1) : 0;
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div class="grid lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.8fr)]">
                <div class="p-6 sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Held now</p>
                    <p class="mt-2 text-4xl font-semibold tracking-tight text-slate-950 tabular-nums dark:text-white">{{ $money($held) }}</p>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">This is money still on student cards and in canteen floats. A withdrawal from Marz Pay does not change these balances.</p>
                    <div class="mt-6 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="flex h-full">
                            <div class="h-full bg-slate-950" style="width: {{ $cardShare }}%"></div>
                            <div class="h-full bg-sky-500" style="width: {{ $floatShare }}%"></div>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-slate-500">On student cards</dt>
                            <dd class="mt-1 text-lg font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($cardTotal) }}</dd>
                            <dd class="text-xs text-slate-400">{{ number_format($cardShare, 1) }}% · {{ number_format($activeCards) }} active {{ \Illuminate\Support\Str::plural('card', $activeCards) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500">In canteen floats</dt>
                            <dd class="mt-1 text-lg font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($floatTotal) }}</dd>
                            <dd class="text-xs text-slate-400">{{ number_format($floatShare, 1) }}% · available for sales</dd>
                        </div>
                    </dl>
                </div>
                <div class="border-t border-slate-100 bg-slate-50/80 p-6 dark:border-slate-800 dark:bg-slate-900/40 sm:p-8 lg:border-l lg:border-t-0">
                    <dl class="space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Schools</dt>
                            <dd class="mt-1 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ number_format($schools->count()) }}</dd>
                            <dd class="text-sm text-slate-500">With a card or a canteen</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Marz Pay</dt>
                            <dd class="mt-2">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $gateway->hasPayCredentials(),
                                    'bg-amber-100 text-amber-900' => ! $gateway->hasPayCredentials(),
                                ])>{{ $gateway->hasPayCredentials() ? 'Account ready' : 'Account missing' }}</span>
                                @if ($gateway->hasWebhookSecret())
                                    <span class="ml-1 inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Web signing on</span>
                                @endif
                            </dd>
                            <dd class="mt-2 text-sm text-slate-500">
                                <a href="{{ route('pocket-pay.settings.edit') }}" class="font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 hover:decoration-slate-900 dark:text-white">Open account settings</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Reports</dt>
                            <dd class="mt-2 text-sm text-slate-500">Sales, top-ups, reversals, and withdrawals for any period.</dd>
                            <dd class="mt-2">
                                <a href="{{ route('pocket-pay.reports') }}" class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white hover:bg-slate-800">Open money reports</a>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <div class="flex items-end justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">By school</h2>
                        <p class="mt-1 text-sm text-slate-500">Share of the money held right now.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <th class="px-6 py-3 font-semibold">School</th>
                                <th class="px-4 py-3 text-right font-semibold">Cards</th>
                                <th class="px-4 py-3 text-right font-semibold">On cards</th>
                                <th class="px-4 py-3 text-right font-semibold">Canteens</th>
                                <th class="px-4 py-3 text-right font-semibold">Float</th>
                                <th class="px-6 py-3 text-right font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schools as $row)
                                @php $share = $held > 0 ? min(100, round(($row->total / $held) * 100, 1)) : 0; @endphp
                                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-900/40">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-slate-950 dark:text-white">{{ $row->business?->name ?? 'School' }}</p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                                <div class="h-full rounded-full bg-slate-900 dark:bg-white" style="width: {{ $share }}%"></div>
                                            </div>
                                            <span class="text-xs tabular-nums text-slate-400">{{ number_format($share, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right tabular-nums text-slate-600">{{ number_format($row->active_cards) }}<span class="text-slate-300"> / {{ number_format($row->cards) }}</span></td>
                                    <td class="px-4 py-4 text-right tabular-nums text-slate-900 dark:text-white">{{ $money($row->card_balance) }}</td>
                                    <td class="px-4 py-4 text-right tabular-nums text-slate-600">{{ number_format($row->canteens) }}</td>
                                    <td class="px-4 py-4 text-right tabular-nums text-slate-900 dark:text-white">{{ $money($row->float_balance) }}</td>
                                    <td class="px-6 py-4 text-right text-base font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($row->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">No school has a Pocket Pay card or canteen yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">Send from Marz Pay</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Leaves the Pocket Pay Marz Pay account. Card balances and canteen floats stay as they are.</p>

                <form method="POST" action="{{ route('pocket-pay.withdrawals.store') }}" class="mt-5 space-y-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                    @csrf
                    <p class="text-sm font-semibold text-slate-950 dark:text-white">Mobile money</p>
                    <label class="block text-xs font-medium text-slate-500">Amount (UGX)
                        <input type="number" name="amount" min="500" step="1" required value="{{ old('phone_number') ? old('amount') : '' }}" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-xs font-medium text-slate-500">Mobile money number
                        <input type="text" name="phone_number" required value="{{ old('phone_number') }}" placeholder="07…" class="{{ $field }} mt-1">
                    </label>
                    @error('phone_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <label class="block text-xs font-medium text-slate-500">Note
                        <input type="text" name="notes" value="{{ old('notes') }}" class="{{ $field }} mt-1">
                    </label>
                    <button class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-slate-950 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled(! $gateway->hasPayCredentials())>Withdraw</button>
                </form>

                <form method="POST" action="{{ route('pocket-pay.withdrawals.bank') }}" class="mt-6 space-y-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                    @csrf
                    <p class="text-sm font-semibold text-slate-950 dark:text-white">Bank</p>
                    <label class="block text-xs font-medium text-slate-500">Amount (UGX)
                        <input type="number" name="amount" min="500" step="1" required value="{{ old('bank_account_number') ? old('amount') : '' }}" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-xs font-medium text-slate-500">Bank name
                        <input type="text" name="bank_name" required value="{{ old('bank_name') }}" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-xs font-medium text-slate-500">Account number
                        <input type="text" name="bank_account_number" required value="{{ old('bank_account_number') }}" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-xs font-medium text-slate-500">Account name
                        <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" class="{{ $field }} mt-1">
                    </label>
                    @error('bank_account_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <button class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-slate-200 text-sm font-semibold text-slate-900 hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-400 dark:border-slate-700 dark:text-white" @disabled(! $gateway->hasPayCredentials())>Withdraw to bank</button>
                </form>
            </section>
        </div>

        @if ($withdrawals->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Recent withdrawals</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest sends from the Pocket Pay Marz Pay account.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <th class="px-6 py-3 font-semibold">When</th>
                                <th class="px-4 py-3 font-semibold">To</th>
                                <th class="px-4 py-3 font-semibold">By</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 text-right font-semibold">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($withdrawals as $withdrawal)
                                <tr class="border-b border-slate-50 last:border-0 dark:border-slate-800">
                                    <td class="px-6 py-3.5 text-slate-600">{{ $withdrawal->created_at->timezone(config('app.timezone'))->format('j M Y, g:i A') }}</td>
                                    <td class="px-4 py-3.5 font-medium text-slate-950 dark:text-white">{{ $withdrawal->method === 'bank' ? trim($withdrawal->bank_name.' · '.$withdrawal->bank_account_number) : $withdrawal->phone_number }}</td>
                                    <td class="px-4 py-3.5 text-slate-600">{{ $withdrawal->requestedBy?->name ?? '—' }}</td>
                                    <td class="px-4 py-3.5">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $withdrawal->status === 'completed',
                                            'bg-amber-100 text-amber-900' => $withdrawal->status === 'processing',
                                            'bg-rose-100 text-rose-800' => $withdrawal->status === 'failed',
                                            'bg-slate-100 text-slate-600' => ! in_array($withdrawal->status, ['completed', 'processing', 'failed'], true),
                                        ])>{{ ucfirst($withdrawal->status) }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($withdrawal->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Student cards</h2>
                    <p class="mt-1 text-sm text-slate-500">Current balance on each card. Open a student to see the statement.</p>
                </div>
                <form method="GET" action="{{ route('pocket-pay.overview') }}" class="flex flex-wrap items-end gap-2">
                    <label class="text-xs font-medium text-slate-500">School
                        <select name="school" class="{{ $field }} mt-1">
                            <option value="">All schools</option>
                            @foreach ($schoolFilter as $school)
                                <option value="{{ $school->id }}" @selected((string) request('school') === (string) $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-medium text-slate-500">Search
                        <input name="q" value="{{ request('q') }}" placeholder="Name, student ID, or card" class="{{ $field }} mt-1 w-56">
                    </label>
                    <button class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white hover:bg-slate-800">Search</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <th class="px-6 py-3 font-semibold">Student</th>
                            <th class="px-4 py-3 font-semibold">School</th>
                            <th class="px-4 py-3 font-semibold">Card</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 text-right font-semibold">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $card)
                            <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/80 dark:border-slate-800">
                                <td class="px-6 py-4">
                                    <a href="{{ route('pocket-pay.cards.show', $card) }}" class="font-medium text-slate-950 underline decoration-transparent underline-offset-4 hover:decoration-slate-400 dark:text-white">{{ $card->student?->full_name ?? 'Student' }}</a>
                                    <span class="mt-0.5 block text-xs text-slate-400">{{ $card->student?->student_id }}</span>
                                </td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $card->business?->name }}</td>
                                <td class="px-4 py-4 font-mono text-xs tracking-wide text-slate-700 dark:text-slate-300">{{ \App\Models\PocketPayCardNumber::format($card->card_number) }}</td>
                                <td class="px-4 py-4">
                                    <span @class([
                                        'rounded-full px-2 py-1 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $card->status === 'active',
                                        'bg-slate-100 text-slate-600' => $card->status === 'inactive',
                                        'bg-rose-100 text-rose-800' => $card->status === 'blocked',
                                    ])>{{ ucfirst($card->status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-base font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($card->balance) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-slate-500">No student cards match this search.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($students->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $students->links() }}</div>
            @endif
        </section>
    </x-pocket-pay.shell>
</x-app-layout>
