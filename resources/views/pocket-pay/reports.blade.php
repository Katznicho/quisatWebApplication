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
    @endphp
    <div class="bg-slate-50 py-10 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                @include('pocket-pay.partials.nav')
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Money reports</h3>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500">Balances are what is held now. Sales, top-ups, deposits, movements, reversals, and withdrawals follow the dates below. {{ $periodLabel }}.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $download('pdf') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-100">PDF</a>
                        <a href="{{ $download('excel') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-100">Excel</a>
                        <a href="{{ $download('pdf', 'everything') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Everything PDF</a>
                        <a href="{{ $download('excel', 'everything') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Everything Excel</a>
                    </div>
                </div>

                <form method="GET" action="{{ route('pocket-pay.reports') }}" class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <select name="report" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        @foreach ($reports as $key => $label)
                            <option value="{{ $key }}" @selected($report === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="range" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <option value="month" @selected($range === 'month')>This month</option>
                        <option value="year" @selected($range === 'year')>This year</option>
                        <option value="all" @selected($range === 'all')>All time</option>
                        <option value="custom" @selected($range === 'custom')>Custom dates</option>
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                    <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                    <select name="school" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                        <option value="">All schools</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected((string) request('school') === (string) $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Apply</button>
                </form>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl bg-slate-900 p-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-300">Held now</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums">{{ $money($summary['held']) }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ $money($summary['card_balance']) }} on cards · {{ $money($summary['float_balance']) }} in floats</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sales</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($summary['sales']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $money($summary['reversed']) }} reversed · {{ number_format($summary['pending_reversals']) }} waiting</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Money in</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($summary['topups'] + $summary['deposits']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $money($summary['topups']) }} mobile money · {{ $money($summary['deposits']) }} float deposits</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Withdrawn</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($summary['withdrawals']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">Completed from the Pocket Pay Marz Pay account. Charges {{ $money($summary['topup_charges']) }}.</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $reports[$report] }}</h3>
                    @if ($report === 'withdrawals')
                        <p class="mt-1 text-sm text-slate-500">Withdrawals leave the Pocket Pay Marz Pay account. The school filter does not apply to this list.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900">
                            <tr>
                                @foreach ($headings as $heading)
                                    <th @class(['px-4 py-3', 'text-right' => str_contains($heading, '(UGX)')])>{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse ($rows as $row)
                                <tr>
                                    @foreach ($headings as $heading)
                                        <td @class(['px-4 py-3 text-slate-700 dark:text-slate-200', 'text-right tabular-nums font-medium text-slate-900 dark:text-white' => str_contains($heading, '(UGX)')])>
                                            {{ str_contains($heading, '(UGX)') && ($row[$heading] ?? '') !== '' ? $money($row[$heading]) : ($row[$heading] ?? '') }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(count($headings), 1) }}" class="px-6 py-10 text-center text-slate-500">Nothing matches this report.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($rows->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-700">{{ $rows->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
