<x-app-layout>
    @php
        $money = fn ($amount) => 'UGX '.number_format((float) $amount);
        $held = $cardTotal + $floatTotal;
    @endphp
    <div class="bg-slate-50 py-10 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                @include('pocket-pay.partials.nav')
            </div>

            @unless ($gateway->hasPayCredentials())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950">
                    <p>Add the Pocket Pay Marz Pay account before collections and withdrawals can run.</p>
                    <a href="{{ route('pocket-pay.settings.edit') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Marz Pay account</a>
                </div>
            @endunless

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl bg-slate-900 p-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-300">Total held</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight">{{ $money($held) }}</p>
                    <p class="mt-2 text-sm text-slate-300">Student cards and canteen floats together.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">On student cards</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ $money($cardTotal) }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ number_format($activeCards) }} active {{ \Illuminate\Support\Str::plural('card', $activeCards) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">In canteen floats</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ $money($floatTotal) }}</p>
                    <p class="mt-2 text-sm text-slate-500">Available for canteens to accept sales.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Schools</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ number_format($schools->count()) }}</p>
                    <p class="mt-2 text-sm text-slate-500">Schools with a card or a canteen.</p>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Withdraw from Marz Pay</h3>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500">Sends from the Pocket Pay Marz Pay account. Student card balances and canteen floats stay as they are.</p>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <form method="POST" action="{{ route('pocket-pay.withdrawals.store') }}" class="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        @csrf
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Mobile money</p>
                        <input type="number" name="amount" min="500" step="1" required placeholder="Amount" value="{{ old('phone_number') ? old('amount') : '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <input type="text" name="phone_number" required placeholder="Mobile money number" value="{{ old('phone_number') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        @error('phone_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <input type="text" name="notes" placeholder="Note (optional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @disabled(! $gateway->hasPayCredentials())>Withdraw</button>
                    </form>
                    <form method="POST" action="{{ route('pocket-pay.withdrawals.bank') }}" class="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        @csrf
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Bank</p>
                        <input type="number" name="amount" min="500" step="1" required placeholder="Amount" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <input type="text" name="bank_name" required placeholder="Bank name" value="{{ old('bank_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <input type="text" name="bank_account_number" required placeholder="Account number" value="{{ old('bank_account_number') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <input type="text" name="bank_account_name" placeholder="Account name (optional)" value="{{ old('bank_account_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        @error('bank_account_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @disabled(! $gateway->hasPayCredentials())>Withdraw to bank</button>
                    </form>
                </div>
                @if ($withdrawals->isNotEmpty())
                    <ul class="mt-5 divide-y divide-slate-100 text-sm dark:divide-slate-700">
                        @foreach ($withdrawals as $withdrawal)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <span class="text-slate-700 dark:text-slate-300">
                                    {{ $withdrawal->method === 'bank' ? $withdrawal->bank_name.' · '.$withdrawal->bank_account_number : $withdrawal->phone_number }}
                                    <span class="text-slate-400">· {{ $withdrawal->created_at->timezone(config('app.timezone'))->format('j M Y g:i A') }}</span>
                                </span>
                                <span class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($withdrawal->amount) }} · {{ ucfirst($withdrawal->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Schools</h3>
                    <p class="mt-1 text-sm text-slate-500">What each school is holding on cards and in canteen floats.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3">School</th>
                                <th class="px-6 py-3 text-right">Cards</th>
                                <th class="px-6 py-3 text-right">On cards</th>
                                <th class="px-6 py-3 text-right">Canteens</th>
                                <th class="px-6 py-3 text-right">Float</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse ($schools as $row)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ $row->business?->name ?? 'School' }}</td>
                                    <td class="px-6 py-4 text-right tabular-nums text-slate-600">{{ number_format($row->active_cards) }} / {{ number_format($row->cards) }}</td>
                                    <td class="px-6 py-4 text-right tabular-nums text-slate-900 dark:text-white">{{ $money($row->card_balance) }}</td>
                                    <td class="px-6 py-4 text-right tabular-nums text-slate-600">{{ number_format($row->canteens) }}</td>
                                    <td class="px-6 py-4 text-right tabular-nums text-slate-900 dark:text-white">{{ $money($row->float_balance) }}</td>
                                    <td class="px-6 py-4 text-right font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($row->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">No school has a Pocket Pay card or canteen yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Students</h3>
                        <p class="mt-1 text-sm text-slate-500">Balance on each student card.</p>
                    </div>
                    <form method="GET" action="{{ route('pocket-pay.overview') }}" class="flex flex-wrap gap-2">
                        <select name="school" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">All schools</option>
                            @foreach ($schoolFilter as $school)
                                <option value="{{ $school->id }}" @selected((string) request('school') === (string) $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </select>
                        <input name="q" value="{{ request('q') }}" placeholder="Name, student ID, or card" class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Search</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3">Student</th>
                                <th class="px-6 py-3">School</th>
                                <th class="px-6 py-3">Card</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse ($students as $card)
                                <tr>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('pocket-pay.cards.show', $card) }}" class="font-medium text-slate-900 hover:text-blue-700 dark:text-white">{{ $card->student?->full_name ?? 'Student' }}</a>
                                        <span class="block text-xs text-slate-500">{{ $card->student?->student_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $card->business?->name }}</td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-700 dark:text-slate-300">{{ \App\Models\PocketPayCardNumber::format($card->card_number) }}</td>
                                    <td class="px-6 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-800' => $card->status === 'active',
                                            'bg-slate-100 text-slate-600' => $card->status === 'inactive',
                                            'bg-rose-50 text-rose-800' => $card->status === 'blocked',
                                        ])>{{ ucfirst($card->status) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($card->balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">No student cards match this search.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($students->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-700">{{ $students->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
