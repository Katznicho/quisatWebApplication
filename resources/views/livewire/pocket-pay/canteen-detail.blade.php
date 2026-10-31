@php
    $summary = $report['summary'];
    $school = $report['school'];
    $money = fn ($amount) => 'UGX '.number_format((float) $amount);
    $periodLabel = $report['from']->isSameDay($report['to'])
        ? $report['from']->format('D, d M Y')
        : $report['from']->format('d M Y').' – '.$report['to']->format('d M Y');
    $reportUrl = fn (string $type) => route('pocket-pay.canteens.report', ['canteen' => $canteen, 'type' => $type, 'from' => $from, 'to' => $to]);
    $tabs = [
        'overview' => 'Overview',
        'sales' => 'Sales',
        'statement' => 'Float statement',
        'attendants' => 'Attendants',
        'students' => 'Students & cards',
        'reversals' => 'Reversals',
    ];
    $statusPill = [
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'pending_setup' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'inactive' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'reversed' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'blocked' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'disabled' => 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-gray-500/10 dark:text-gray-400',
    ];
    $pill = fn (?string $state) => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.($statusPill[$state] ?? $statusPill['disabled']);
    $th = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $thRight = 'px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $td = 'px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap';
    $tdRight = 'px-4 py-3 text-sm text-right tabular-nums whitespace-nowrap text-gray-700 dark:text-gray-300';
    $card = 'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800';
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                    <x-heroicon-o-building-storefront class="h-7 w-7" />
                </div>
                <div>
                    <nav class="text-xs text-gray-500 dark:text-gray-400">
                        <a href="{{ route('pocket-pay.canteens.index') }}" class="hover:text-blue-600">Pocket Pay</a>
                        <span class="mx-1">/</span>
                        <a href="{{ route('pocket-pay.canteens.index') }}" class="hover:text-blue-600">Canteens</a>
                    </nav>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $canteen->name }}</h1>
                        <span class="{{ $pill($canteen->status) }}">{{ ucfirst($canteen->status) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $canteen->location ?: 'No location set' }}
                        <span class="mx-1.5">·</span> Opened {{ $canteen->created_at?->format('d M Y') }}
                        @if ($canteen->low_float_threshold > 0)
                            <span class="mx-1.5">·</span> Low-float alert at {{ $money($canteen->low_float_threshold) }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{ $this->topupAction }}
                {{ $this->editAction }}
                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                        <x-heroicon-o-document-arrow-down class="h-4 w-4" />
                        Reports
                        <x-heroicon-m-chevron-down class="h-4 w-4" />
                    </button>
                    <div x-show="open" x-transition x-cloak
                        class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <p class="px-3 pb-1 pt-2 text-xs text-gray-500 dark:text-gray-400">PDF for {{ $periodLabel }}</p>
                        @foreach ([
                            'full' => ['Full canteen report', 'Summary, trends, attendants, students, statement and sales'],
                            'statement' => ['Float statement', 'Opening and closing balance with every movement'],
                            'sales' => ['Sales register', 'Every sale with attendant performance'],
                            'students' => ['Students & cards', 'Cardholders and what they spent here'],
                        ] as $type => [$title, $hint])
                            <a href="{{ $reportUrl($type) }}" target="_blank"
                                class="block rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $title }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- School --}}
        <div class="flex flex-col gap-4 border-t border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/40 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                @if ($school['logo_url'])
                    <img src="{{ $school['logo_url'] }}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                @else
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-sm font-bold text-blue-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        {{ str($school['name'])->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                    </div>
                @endif
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">School</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $school['name'] }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-600 dark:text-gray-400 sm:ml-auto">
                @if ($school['address'])
                    <span class="inline-flex items-center gap-1.5"><x-heroicon-o-map-pin class="h-4 w-4" />{{ $school['address'] }}</span>
                @endif
                @if ($school['phone'])
                    <span class="inline-flex items-center gap-1.5"><x-heroicon-o-phone class="h-4 w-4" />{{ $school['phone'] }}</span>
                @endif
                @if ($school['email'])
                    <span class="inline-flex items-center gap-1.5"><x-heroicon-o-envelope class="h-4 w-4" />{{ $school['email'] }}</span>
                @endif
                <span class="inline-flex items-center gap-1.5"><x-heroicon-o-credit-card class="h-4 w-4" />{{ number_format($summary['cardholders']) }} cards · {{ number_format($summary['active_cards']) }} active</span>
            </div>
        </div>
    </div>

    @if ($canteen->isFloatLow())
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
            <p>The float is <strong>{{ $money($summary['float_balance']) }}</strong>, below the {{ $money($canteen->low_float_threshold) }} alert level. Sales stop when the float can't cover a purchase.</p>
        </div>
    @endif

    {{-- Period --}}
    <div class="{{ $card }} flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Reporting period</p>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $periodLabel }} <span class="font-normal text-gray-500">· {{ $report['timezone'] }}</span></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex flex-wrap rounded-lg bg-gray-100 p-1 text-xs font-medium dark:bg-gray-900">
                @foreach (['today' => 'Today', '7d' => '7 days', '30d' => '30 days', 'month' => 'This month', 'last_month' => 'Last month', 'year' => 'This year'] as $key => $label)
                    <button type="button" wire:click="preset('{{ $key }}')"
                        class="rounded-md px-2.5 py-1.5 text-gray-600 hover:bg-white hover:text-gray-900 hover:shadow-sm dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">{{ $label }}</button>
                @endforeach
            </div>
            <div class="flex items-center gap-2 text-sm">
                <input type="date" wire:model.live="from" max="{{ $to }}"
                    class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                <span class="text-gray-400">to</span>
                <input type="date" wire:model.live="to" min="{{ $from }}"
                    class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Net sales', $money($summary['net_sales']), $summary['reversed_amount'] > 0 ? $money($summary['reversed_amount']).' reversed' : 'No reversals', 'heroicon-o-banknotes', 'text-blue-600 bg-blue-50 dark:bg-blue-500/10'],
            ['Transactions', number_format($summary['transactions']), 'Avg '.$money($summary['average_sale']), 'heroicon-o-receipt-percent', 'text-violet-600 bg-violet-50 dark:bg-violet-500/10'],
            ['Students served', number_format($summary['students_served']), 'of '.number_format($summary['cardholders']).' cardholders', 'heroicon-o-academic-cap', 'text-teal-600 bg-teal-50 dark:bg-teal-500/10'],
            ['Float balance', $money($summary['float_balance']), $summary['pending_topups'] > 0 ? $money($summary['pending_topups']).' top-up pending' : 'Current balance', 'heroicon-o-wallet', $canteen->isFloatLow() ? 'text-rose-600 bg-rose-50 dark:bg-rose-500/10' : 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10'],
            ['Float top-ups', $money($summary['float_topups']), 'Credited in period', 'heroicon-o-arrow-down-tray', 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10'],
            ['Opening float', $money($summary['float_opening']), 'Closing '.$money($summary['float_closing']), 'heroicon-o-scale', 'text-gray-600 bg-gray-100 dark:bg-gray-500/10'],
            ['Reversals', number_format($summary['reversed_count']), $money($summary['reversed_amount']).' refunded', 'heroicon-o-arrow-uturn-left', 'text-rose-600 bg-rose-50 dark:bg-rose-500/10'],
            ['Attendants', number_format($summary['active_attendants']).' / '.number_format($summary['attendants']), 'Active / total', 'heroicon-o-user-group', 'text-amber-600 bg-amber-50 dark:bg-amber-500/10'],
        ] as [$label, $value, $hint, $icon, $tone])
            <div class="{{ $card }} p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <span class="rounded-lg p-1.5 {{ $tone }}"><x-dynamic-component :component="$icon" class="h-4 w-4" /></span>
                </div>
                <p class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $value }}</p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    @if ($summary['pending_reversals'] > 0 && $tab !== 'reversals')
        <button type="button" wire:click="setTab('reversals')"
            class="flex w-full items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-left text-sm text-amber-800 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <span><strong>{{ $summary['pending_reversals'] }}</strong> reversal {{ str('request')->plural($summary['pending_reversals']) }} ({{ $money($summary['pending_reversals_amount']) }}) waiting for approval.</span>
            <span class="font-semibold">Review →</span>
        </button>
    @endif

    {{-- Tabs --}}
    <div class="{{ $card }}">
        <div class="overflow-x-auto border-b border-gray-200 dark:border-gray-700">
            <nav class="flex min-w-max gap-1 px-4">
                @foreach ($tabs as $key => $label)
                    <button type="button" wire:click="setTab('{{ $key }}')" @class([
                        '-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-3.5 text-sm font-medium',
                        'border-blue-600 text-blue-600 dark:text-blue-400' => $tab === $key,
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $key,
                    ])>
                        {{ $label }}
                        @if ($key === 'reversals' && $summary['pending_reversals'] > 0)
                            <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-bold text-amber-950">{{ $summary['pending_reversals'] }}</span>
                        @elseif ($key === 'attendants')
                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $summary['attendants'] }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            {{-- Overview --}}
            @if ($tab === 'overview')
                @php
                    $daily = $report['daily'];
                    $maxDay = max(1, max(array_column($daily, 'amount')));
                    $labelEvery = max(1, (int) ceil(count($daily) / 15));
                    $hours = collect($report['hourly'])->filter(fn ($h, $hour) => $h['count'] > 0 || ($hour >= 7 && $hour <= 17));
                    $maxHour = max(1, $hours->max('amount'));
                    $bestDay = collect($daily)->sortByDesc('amount')->first();
                @endphp
                <div class="grid gap-6 p-6 xl:grid-cols-3">
                    <div class="xl:col-span-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daily sales</h3>
                            @if ($bestDay && $bestDay['amount'] > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400">Best day: {{ $bestDay['date']->format('D d M') }} · {{ $money($bestDay['amount']) }}</p>
                            @endif
                        </div>
                        <div class="mt-4 flex h-56 items-end gap-px border-b border-l border-gray-200 pl-px dark:border-gray-700">
                            @foreach ($daily as $day)
                                <div class="group relative flex h-full flex-1 items-end">
                                    <div class="w-full rounded-t bg-blue-500/80 transition group-hover:bg-blue-600 dark:bg-blue-500/70"
                                        style="height: {{ $day['amount'] > 0 ? max(2, round($day['amount'] / $maxDay * 100, 1)) : 0 }}%"></div>
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white shadow group-hover:block">
                                        {{ $day['date']->format('D d M') }}<br>{{ $money($day['amount']) }} · {{ $day['count'] }} sales
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-1 flex gap-px pl-px">
                            @foreach ($daily as $i => $day)
                                <div class="flex-1 text-center text-[10px] text-gray-400">{{ $i % $labelEvery === 0 ? $day['date']->format('d/m') : '' }}</div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Float movement</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Opening balance</dt><dd class="font-medium tabular-nums text-gray-900 dark:text-white">{{ $money($summary['float_opening']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Top-ups</dt><dd class="tabular-nums text-emerald-600">+ {{ $money($summary['float_topups']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Reversals returned</dt><dd class="tabular-nums text-emerald-600">+ {{ $money($summary['float_credits'] - $summary['float_topups']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Sales</dt><dd class="tabular-nums text-rose-600">− {{ $money($summary['float_debits']) }}</dd></div>
                            <div class="flex justify-between border-t border-gray-200 pt-3 dark:border-gray-700"><dt class="font-semibold text-gray-900 dark:text-white">Closing balance</dt><dd class="font-bold tabular-nums text-gray-900 dark:text-white">{{ $money($summary['float_closing']) }}</dd></div>
                        </dl>
                        <button type="button" wire:click="setTab('statement')" class="mt-5 text-sm font-medium text-blue-600 hover:text-blue-800">View full statement →</button>
                    </div>

                    <div class="xl:col-span-3 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between px-5 py-4">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Attendant performance</h3>
                                <button type="button" wire:click="setTab('attendants')" class="text-xs font-medium text-blue-600 hover:text-blue-800">All attendants →</button>
                            </div>
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/40"><tr><th class="{{ $th }}">Attendant</th><th class="{{ $thRight }}">Sales</th><th class="{{ $thRight }}">Amount</th><th class="{{ $thRight }}">Share</th></tr></thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($report['attendants']->take(6) as $row)
                                        <tr>
                                            <td class="{{ $td }}"><span class="font-medium text-gray-900 dark:text-white">{{ $row['attendant']->name }}</span></td>
                                            <td class="{{ $tdRight }}">{{ number_format($row['count']) }}</td>
                                            <td class="{{ $tdRight }}">{{ $money($row['amount']) }}</td>
                                            <td class="{{ $tdRight }}">
                                                @php $share = $summary['net_sales'] > 0 ? $row['amount'] / $summary['net_sales'] * 100 : 0; @endphp
                                                <div class="ml-auto flex w-24 items-center gap-2">
                                                    <div class="h-1.5 flex-1 rounded-full bg-gray-100 dark:bg-gray-700"><div class="h-1.5 rounded-full bg-blue-500" style="width: {{ round($share) }}%"></div></div>
                                                    <span class="w-8 text-xs">{{ round($share) }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No attendants yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between px-5 py-4">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top students</h3>
                                <button type="button" wire:click="setTab('students')" class="text-xs font-medium text-blue-600 hover:text-blue-800">All cardholders →</button>
                            </div>
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/40"><tr><th class="{{ $th }}">Student</th><th class="{{ $thRight }}">Purchases</th><th class="{{ $thRight }}">Spent</th></tr></thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($report['top_students']->take(6) as $row)
                                        <tr>
                                            <td class="{{ $td }}">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $row['student']?->full_name ?? 'Unknown' }}</span>
                                                <span class="block text-xs text-gray-500">{{ $row['student']?->classRoom?->name ?? '—' }}</span>
                                            </td>
                                            <td class="{{ $tdRight }}">{{ number_format($row['count']) }}</td>
                                            <td class="{{ $tdRight }}">{{ $money($row['amount']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No purchases in this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="xl:col-span-3 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Busiest hours</h3>
                        <div class="mt-4 grid gap-2" style="grid-template-columns: repeat({{ max(1, $hours->count()) }}, minmax(0, 1fr))">
                            @foreach ($hours as $hour => $h)
                                <div class="group relative text-center">
                                    <div class="flex h-24 items-end rounded bg-gray-50 dark:bg-gray-900/40">
                                        <div class="w-full rounded bg-violet-500/80" style="height: {{ $h['amount'] > 0 ? max(3, round($h['amount'] / $maxHour * 100)) : 0 }}%"></div>
                                    </div>
                                    <p class="mt-1 text-[10px] text-gray-500">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</p>
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white group-hover:block">{{ $h['count'] }} sales · {{ $money($h['amount']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Sales --}}
            @if ($tab === 'sales')
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row">
                        <div class="relative sm:w-80">
                            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Receipt, student or card number"
                                class="w-full rounded-lg border-gray-300 py-2 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                        <select wire:model.live="status" class="rounded-lg border-gray-300 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                            <option value="">All statuses</option>
                            <option value="completed">Completed</option>
                            <option value="reversed">Reversed</option>
                        </select>
                    </div>
                    <a href="{{ $reportUrl('sales') }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800"><x-heroicon-o-document-arrow-down class="h-4 w-4" /> Sales register PDF</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Date & time</th><th class="{{ $th }}">Receipt</th><th class="{{ $th }}">Student</th><th class="{{ $th }}">Card</th><th class="{{ $th }}">Attendant</th><th class="{{ $th }}">Item</th><th class="{{ $thRight }}">Amount</th><th class="{{ $th }}">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($sales as $sale)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}">{{ $sale->created_at->setTimezone($report['timezone'])->format('d M Y, H:i') }}</td>
                                    <td class="{{ $td }} font-mono text-xs">{{ $sale->receipt_number }}</td>
                                    <td class="{{ $td }}"><span class="font-medium text-gray-900 dark:text-white">{{ $sale->student?->full_name ?? '—' }}</span><span class="block text-xs text-gray-500">{{ $sale->student?->classRoom?->name }}</span></td>
                                    <td class="{{ $td }} font-mono text-xs">{{ $sale->card?->maskedNumber() ?? '—' }}</td>
                                    <td class="{{ $td }}">{{ $sale->attendant?->name ?? '—' }}</td>
                                    <td class="{{ $td }} max-w-[14rem] truncate">{{ $sale->description ?: '—' }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($sale->amount) }}</td>
                                    <td class="{{ $td }}">
                                        <span class="{{ $pill($sale->status) }}">{{ ucfirst($sale->status) }}</span>
                                        @if ($sale->reversal_status === 'pending')
                                            <span class="{{ $pill('pending') }} ml-1">Reversal pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">No sales match this period and filter.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($sales->total() > 0)
                            <tfoot class="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">Period total · {{ number_format($summary['transactions']) }} completed sales</td>
                                    <td class="{{ $tdRight }} font-bold text-gray-900 dark:text-white">{{ $money($summary['net_sales']) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $sales->links() }}</div>
            @endif

            {{-- Float statement --}}
            @if ($tab === 'statement')
                <div class="grid grid-cols-2 gap-px border-b border-gray-200 bg-gray-200 dark:border-gray-700 dark:bg-gray-700 md:grid-cols-4">
                    @foreach ([['Opening balance', $summary['float_opening'], ''], ['Total credits', $summary['float_credits'], 'text-emerald-600'], ['Total debits', $summary['float_debits'], 'text-rose-600'], ['Closing balance', $summary['float_closing'], '']] as [$label, $value, $tone])
                        <div class="bg-white p-4 dark:bg-gray-800">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                            <p class="mt-1 text-lg font-bold tabular-nums {{ $tone ?: 'text-gray-900 dark:text-white' }}">{{ $money($value) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end p-4">
                    <a href="{{ $reportUrl('statement') }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800"><x-heroicon-o-document-arrow-down class="h-4 w-4" /> Statement PDF</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Date</th><th class="{{ $th }}">Reference</th><th class="{{ $th }}">Description</th><th class="{{ $th }}">Type</th><th class="{{ $thRight }}">Debit</th><th class="{{ $thRight }}">Credit</th><th class="{{ $thRight }}">Balance</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @if ($ledger->onFirstPage())
                                <tr class="bg-blue-50/50 dark:bg-blue-500/5">
                                    <td class="{{ $td }}">{{ $report['from']->format('d M Y') }}</td>
                                    <td class="{{ $td }}"></td>
                                    <td class="{{ $td }} font-semibold text-gray-900 dark:text-white" colspan="4">Opening balance</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($summary['float_opening']) }}</td>
                                </tr>
                            @endif
                            @forelse ($ledger as $entry)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}">{{ $entry->created_at->setTimezone($report['timezone'])->format('d M Y, H:i') }}</td>
                                    <td class="{{ $td }} font-mono text-xs">{{ strtoupper(substr($entry->uuid, 0, 8)) }}</td>
                                    <td class="{{ $td }}">{{ $entry->description }}</td>
                                    <td class="{{ $td }}"><span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $entry->type }}</span></td>
                                    <td class="{{ $tdRight }} text-rose-600">{{ $entry->direction === 'debit' ? number_format($entry->amount) : '' }}</td>
                                    <td class="{{ $tdRight }} text-emerald-600">{{ $entry->direction === 'credit' ? number_format($entry->amount) : '' }}</td>
                                    <td class="{{ $tdRight }} font-medium text-gray-900 dark:text-white">{{ number_format($entry->balance_after) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No float movements in this period.</td></tr>
                            @endforelse
                            @if (! $ledger->hasMorePages())
                                <tr class="bg-blue-50/50 dark:bg-blue-500/5">
                                    <td class="{{ $td }}">{{ $report['to']->format('d M Y') }}</td>
                                    <td class="{{ $td }}"></td>
                                    <td class="{{ $td }} font-semibold text-gray-900 dark:text-white" colspan="2">Closing balance</td>
                                    <td class="{{ $tdRight }} font-semibold text-rose-600">{{ number_format($summary['float_debits']) }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-emerald-600">{{ number_format($summary['float_credits']) }}</td>
                                    <td class="{{ $tdRight }} font-bold text-gray-900 dark:text-white">{{ $money($summary['float_closing']) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $ledger->links() }}</div>
            @endif

            {{-- Attendants --}}
            @if ($tab === 'attendants')
                <div class="flex items-center justify-between p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sales figures cover {{ $periodLabel }}.</p>
                    {{ $this->addAttendantAction }}
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Attendant</th><th class="{{ $th }}">Phone</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Last login</th><th class="{{ $thRight }}">Sales</th><th class="{{ $thRight }}">Amount</th><th class="{{ $thRight }}">Reversed</th><th class="{{ $th }}">Last sale</th><th class="{{ $thRight }}">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($report['attendants'] as $row)
                                @php $attendant = $row['attendant']; @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">{{ mb_strtoupper(mb_substr($attendant->name, 0, 1)) }}</div>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $attendant->name }}</span>
                                        </div>
                                    </td>
                                    <td class="{{ $td }}">{{ $attendant->phone }}</td>
                                    <td class="{{ $td }}">
                                        @if ($attendant->trashed())
                                            <span class="{{ $pill('disabled') }}">Removed</span>
                                        @else
                                            <span class="{{ $pill($attendant->status) }}">{{ str($attendant->status)->replace('_', ' ')->title() }}</span>
                                        @endif
                                        @if ($attendant->isLocked())
                                            <span class="{{ $pill('reversed') }} ml-1">PIN locked</span>
                                        @endif
                                    </td>
                                    <td class="{{ $td }}">{{ $attendant->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="{{ $tdRight }}">{{ number_format($row['count']) }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($row['amount']) }}</td>
                                    <td class="{{ $tdRight }}">{{ $row['reversed'] }}</td>
                                    <td class="{{ $td }}">{{ $row['last_sale_at']?->setTimezone($report['timezone'])->format('d M, H:i') ?? '—' }}</td>
                                    <td class="{{ $tdRight }}">
                                        @unless ($attendant->trashed())
                                            <div class="flex items-center justify-end gap-3">
                                                @if ($attendant->status === \App\Models\CanteenAttendant::STATUS_DISABLED)
                                                    {{ ($this->enableAttendantAction)(['attendant' => $attendant->id]) }}
                                                @else
                                                    {{ ($this->sendOtpAction)(['attendant' => $attendant->id]) }}
                                                    {{ ($this->disableAttendantAction)(['attendant' => $attendant->id]) }}
                                                @endif
                                            </div>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-gray-500">No attendants yet. Add the people who will sell at this canteen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Students & cards --}}
            @if ($tab === 'students')
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative sm:w-80">
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Student name, admission no. or card"
                            class="w-full rounded-lg border-gray-300 py-2 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">All {{ number_format($summary['cardholders']) }} cards at {{ $school['name'] }} · spend at this canteen for {{ $periodLabel }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Student</th><th class="{{ $th }}">Class</th><th class="{{ $th }}">Card</th><th class="{{ $th }}">Status</th><th class="{{ $thRight }}">Card balance</th><th class="{{ $thRight }}">Daily limit</th><th class="{{ $thRight }}">Purchases</th><th class="{{ $thRight }}">Spent here</th><th class="{{ $th }}">Last purchase here</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($cardholders as $cardholder)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}"><span class="font-medium text-gray-900 dark:text-white">{{ $cardholder->student?->full_name ?? '—' }}</span><span class="block text-xs text-gray-500">{{ $cardholder->student?->student_id }}</span></td>
                                    <td class="{{ $td }}">{{ $cardholder->student?->classRoom?->name ?? '—' }}</td>
                                    <td class="{{ $td }} font-mono text-xs">{{ $cardholder->maskedNumber() }}</td>
                                    <td class="{{ $td }}">
                                        <span class="{{ $pill($cardholder->status) }}">{{ $cardholder->status === 'inactive' ? 'Not activated' : ucfirst($cardholder->status) }}</span>
                                    </td>
                                    <td class="{{ $tdRight }}">{{ $money($cardholder->balance) }}</td>
                                    <td class="{{ $tdRight }}">{{ $cardholder->daily_limit ? $money($cardholder->daily_limit) : 'None' }}</td>
                                    <td class="{{ $tdRight }}">{{ number_format($cardholder->purchases_here) }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($cardholder->spent_here ?? 0) }}</td>
                                    <td class="{{ $td }}">{{ $cardholder->last_purchase_here ? \Illuminate\Support\Carbon::parse($cardholder->last_purchase_here)->setTimezone($report['timezone'])->format('d M Y, H:i') : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-gray-500">No cards match. Cards are assigned to students by Quisat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $cardholders->links() }}</div>
            @endif

            {{-- Reversals --}}
            @if ($tab === 'reversals')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Requested</th><th class="{{ $th }}">Receipt</th><th class="{{ $th }}">Student</th><th class="{{ $th }}">Attendant</th><th class="{{ $thRight }}">Amount</th><th class="{{ $th }}">Reason</th><th class="{{ $th }}">Decision</th><th class="{{ $thRight }}"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($reversals as $sale)
                                <tr @class(['bg-amber-50/40 dark:bg-amber-500/5' => $sale->reversal_status === 'pending'])>
                                    <td class="{{ $td }}">{{ $sale->reversal_requested_at?->setTimezone($report['timezone'])->format('d M Y, H:i') ?? '—' }}</td>
                                    <td class="{{ $td }} font-mono text-xs">{{ $sale->receipt_number }}<span class="block font-sans text-gray-500">Sold {{ $sale->created_at->setTimezone($report['timezone'])->format('d M, H:i') }}</span></td>
                                    <td class="{{ $td }}">{{ $sale->student?->full_name ?? '—' }}</td>
                                    <td class="{{ $td }}">{{ $sale->attendant?->name ?? '—' }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($sale->amount) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-xs">{{ $sale->reversal_reason }}</td>
                                    <td class="{{ $td }}">
                                        <span class="{{ $pill($sale->reversal_status) }}">{{ ucfirst($sale->reversal_status) }}</span>
                                        @if ($sale->reviewer)
                                            <span class="block text-xs text-gray-500">{{ $sale->reviewer->name }}{{ $sale->reversal_review_note ? ': '.$sale->reversal_review_note : '' }}</span>
                                        @endif
                                    </td>
                                    <td class="{{ $tdRight }}">
                                        @if ($sale->reversal_status === 'pending')
                                            <div class="flex justify-end gap-2">
                                                {{ ($this->approveReversalAction)(['sale' => $sale->id]) }}
                                                {{ ($this->rejectReversalAction)(['sale' => $sale->id]) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">No reversal requests for this canteen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $reversals->links() }}</div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</div>
