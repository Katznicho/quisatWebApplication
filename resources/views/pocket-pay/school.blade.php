<x-app-layout>
    @php
        $money = fn ($amount) => 'UGX '.number_format((float) $amount);
        $field = 'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
        $held = $summary['held'];
        $topupLabel = function ($topup): string {
            $target = $topup->topupable;
            if ($target instanceof \App\Models\Canteen) {
                return $target->name ?: 'Canteen float';
            }
            if ($target instanceof \App\Models\PocketPayCard) {
                return $target->student?->full_name ?: 'Student card';
            }

            return 'Top-up';
        };
    @endphp
    <x-pocket-pay.shell>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('pocket-pay.overview') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">All schools</a>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $business->name }}</h2>
            </div>
            <a href="{{ route('pocket-pay.reports', ['school' => $business->id]) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:bg-gray-900 dark:text-white">School reports</a>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Held now</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($held) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">On student cards</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($summary['card_balance']) }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($summary['active_cards']) }} active</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">In canteen floats</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($summary['float_balance']) }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($canteens->count()) }} {{ \Illuminate\Support\Str::plural('canteen', $canteens->count()) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $periodLabel }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Sales {{ $money($summary['sales']) }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">Top-ups {{ $money($summary['topups']) }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">Float deposits {{ $money($summary['deposits']) }}</p>
                @if ($summary['pending_reversals'] > 0)
                    <a href="{{ route('pocket-pay.reversals.index') }}" class="mt-2 inline-flex text-sm font-medium text-amber-800">{{ $summary['pending_reversals'] }} reversal {{ \Illuminate\Support\Str::plural('request', $summary['pending_reversals']) }}</a>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <h3 class="text-base font-semibold text-slate-950 dark:text-white">Canteens</h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($canteens as $canteen)
                    <a href="{{ route('pocket-pay.canteens.show', $canteen) }}" class="flex items-center justify-between gap-6 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-900/40">
                        <div>
                            <p class="font-medium text-slate-950 dark:text-white">{{ $canteen->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $canteen->collectsSales() ? 'Sales balance' : 'Float' }} · {{ ucfirst($canteen->status) }} · {{ number_format($canteen->attendants_count) }} {{ \Illuminate\Support\Str::plural('attendant', $canteen->attendants_count) }}</p>
                        </div>
                        <p class="font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($canteen->float_balance) }}</p>
                    </a>
                @empty
                    <p class="px-6 py-10 text-sm text-slate-500">This school has no canteen yet.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">Student cards</h3>
                    <p class="mt-1 text-sm text-slate-500">Open a student to see the statement.</p>
                </div>
                <form method="GET" action="{{ route('pocket-pay.schools.show', $business) }}" class="flex flex-wrap items-end gap-2">
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
                            <th class="px-4 py-3 font-semibold">Card</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 text-right font-semibold">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cards as $card)
                            <tr class="border-b border-slate-50 last:border-0 dark:border-slate-800">
                                <td class="px-6 py-4">
                                    <a href="{{ route('pocket-pay.cards.show', $card) }}" class="font-medium text-slate-950 underline decoration-transparent underline-offset-4 hover:decoration-slate-400 dark:text-white">{{ $card->student?->full_name ?? 'Student' }}</a>
                                    <span class="mt-0.5 block text-xs text-slate-400">{{ $card->student?->student_id }}</span>
                                </td>
                                <td class="px-4 py-4 font-mono text-xs tracking-wide text-slate-700 dark:text-slate-300">{{ \App\Models\PocketPayCardNumber::format($card->card_number) }}</td>
                                <td class="px-4 py-4">{{ ucfirst($card->status) }}</td>
                                <td class="px-6 py-4 text-right font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($card->balance) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">No student cards match this search.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($cards->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $cards->links() }}</div>
            @endif
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">Recent sales</h3>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($sales as $sale)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-medium text-slate-950 dark:text-white">{{ $sale->student?->full_name ?? 'Student' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $sale->canteen?->name }} · {{ $sale->created_at->timezone(config('app.timezone'))->format('j M, g:i A') }}</p>
                            </div>
                            <p class="font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($sale->amount) }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-sm text-slate-500">No sales yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">Recent top-ups</h3>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($topups as $topup)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-medium text-slate-950 dark:text-white">{{ $topupLabel($topup) }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ ucfirst($topup->status) }} · {{ $topup->created_at->timezone(config('app.timezone'))->format('j M, g:i A') }}</p>
                            </div>
                            <p class="font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($topup->amount) }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-sm text-slate-500">No mobile-money top-ups yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">Float deposits</h3>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($deposits as $deposit)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-medium text-slate-950 dark:text-white">{{ $deposit->canteen?->name ?? 'Canteen' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $deposit->paid_on?->timezone(config('app.timezone'))->format('j M Y') }}</p>
                            </div>
                            <p class="font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($deposit->amount) }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-sm text-slate-500">No float deposits yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </x-pocket-pay.shell>
</x-app-layout>
