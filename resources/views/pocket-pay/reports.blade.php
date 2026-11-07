<x-app-layout>
    @php
        $money = fn ($amount) => 'UGX '.number_format((float) $amount, 2);
        $download = function (string $format, ?string $report = null) {
            $query = request()->except('page');
            $query['format'] = $format;
            if ($report) {
                $query['report'] = $report;
            }

            return route('pocket-pay.reports.download', $query);
        };
        $range = request()->filled('from') || request()->filled('to') ? 'custom' : request('range', 'month');
        $schoolName = $schools->firstWhere('id', (int) request('school'))?->name;
        $field = 'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
        $button = 'inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold';
    @endphp
    <x-pocket-pay.shell>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $schoolName ?: 'All schools' }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ $periodLabel }}</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Held now is the live balance. Sales, top-ups, deposits, movements, reversals, and withdrawals use the dates below.</p>
                </div>
                <div class="flex flex-wrap gap-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">This report</p>
                        <div class="mt-2 flex gap-2">
                            <a href="{{ $download('pdf') }}" class="{{ $button }} border border-slate-200 text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100">PDF</a>
                            <a href="{{ $download('excel') }}" class="{{ $button }} border border-slate-200 text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100">Excel</a>
                        </div>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Every report</p>
                        <div class="mt-2 flex gap-2">
                            <a href="{{ $download('pdf', 'everything') }}" class="{{ $button }} bg-slate-950 text-white hover:bg-slate-800">PDF</a>
                            <a href="{{ $download('excel', 'everything') }}" class="{{ $button }} bg-slate-950 text-white hover:bg-slate-800">Excel</a>
                        </div>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('pocket-pay.reports') }}" class="mt-6 grid grid-cols-1 gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 md:grid-cols-2 xl:grid-cols-6">
                <label class="text-xs font-medium text-slate-500">Report
                    <select name="report" class="{{ $field }} mt-1">
                        @foreach ($reports as $key => $label)
                            <option value="{{ $key }}" @selected($report === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-medium text-slate-500">Period
                    <select name="range" class="{{ $field }} mt-1">
                        <option value="month" @selected($range === 'month')>This month</option>
                        <option value="year" @selected($range === 'year')>This year</option>
                        <option value="all" @selected($range === 'all')>All time</option>
                        <option value="custom" @selected($range === 'custom')>Custom dates</option>
                    </select>
                </label>
                <label class="text-xs font-medium text-slate-500">From
                    <input type="date" name="from" value="{{ request('from') }}" placeholder="Start date" class="{{ $field }} mt-1">
                </label>
                <label class="text-xs font-medium text-slate-500">To
                    <input type="date" name="to" value="{{ request('to') }}" placeholder="End date" class="{{ $field }} mt-1">
                </label>
                <label class="text-xs font-medium text-slate-500">School
                    <select name="school" class="{{ $field }} mt-1">
                        <option value="">All schools</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected((string) request('school') === (string) $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end">
                    <button class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-slate-950 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <article class="rounded-2xl bg-slate-950 p-5 text-white shadow-sm lg:row-span-1">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Held now</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums tracking-tight">{{ $money($summary['held']) }}</p>
                <dl class="mt-4 space-y-2 text-sm text-slate-300">
                    <div class="flex items-center justify-between gap-3"><dt>On cards</dt><dd class="tabular-nums text-white">{{ $money($summary['card_balance']) }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt>In floats</dt><dd class="tabular-nums text-white">{{ $money($summary['float_balance']) }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt>Active cards</dt><dd class="tabular-nums text-white">{{ number_format($summary['active_cards']) }}</dd></div>
                </dl>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Sales in period</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($summary['sales']) }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $money($summary['reversed']) }} reversed<br>{{ number_format($summary['pending_reversals']) }} still waiting</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Money in</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($summary['topups'] + $summary['deposits']) }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $money($summary['topups']) }} mobile money<br>{{ $money($summary['deposits']) }} float deposits</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-gray-900">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Withdrawn</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($summary['withdrawals']) }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Completed from Marz Pay. Charges collected {{ $money($summary['topup_charges']) }}.</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ $reports[$report] }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($report === 'withdrawals')
                            These leave the Pocket Pay Marz Pay account. The school filter does not apply here.
                        @elseif (in_array($report, ['schools', 'students', 'canteens'], true))
                            Current balances. The date range does not change this list.
                        @else
                            Records inside {{ $periodLabel }}{{ $schoolName ? ' · '.$schoolName : '' }}.
                        @endif
                    </p>
                </div>
                <p class="text-sm tabular-nums text-slate-400">{{ number_format($rows->total()) }} {{ \Illuminate\Support\Str::plural('row', $rows->total()) }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            @foreach ($headings as $heading)
                                <th @class(['whitespace-nowrap px-4 py-3 font-semibold', 'text-right' => str_contains($heading, '(UGX)'), 'pl-6' => $loop->first, 'pr-6' => $loop->last])>{{ \Illuminate\Support\Str::before($heading, ' (UGX)') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/80 dark:border-slate-800">
                                @foreach ($headings as $heading)
                                    <td @class(['whitespace-nowrap px-4 py-3.5 text-slate-700 dark:text-slate-200', 'text-right font-medium tabular-nums text-slate-950 dark:text-white' => str_contains($heading, '(UGX)'), 'pl-6 font-medium text-slate-950 dark:text-white' => $loop->first, 'pr-6' => $loop->last])>
                                        {{ str_contains($heading, '(UGX)') && ($row[$heading] ?? '') !== '' ? $money($row[$heading]) : ($row[$heading] ?? '') }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(count($headings), 1) }}" class="px-6 py-14 text-center text-slate-500">Nothing matches this report.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $rows->links() }}</div>
            @endif
        </section>
    </x-pocket-pay.shell>
</x-app-layout>
