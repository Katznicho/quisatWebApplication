@php
    $summary = $report['summary'];
    $tz = $report['timezone'];
    $student = $card->student;
    $parent = $student?->parentGuardian;
    $studentInfo = \App\Support\PocketPayPresenter::student($student);
    $money = fn ($amount) => 'UGX '.number_format((float) $amount);
    $local = fn ($date, string $format = 'd M Y, H:i') => $date ? \Illuminate\Support\Carbon::parse($date)->setTimezone($tz)->format($format) : '—';
    $actor = fn ($model) => match (true) {
        $model instanceof \App\Models\CanteenAttendant => 'Attendant · '.$model->name,
        $model instanceof \App\Models\ParentGuardian => 'Parent · '.$model->full_name,
        $model instanceof \App\Models\User => $model->name,
        default => '—',
    };
    $periodLabel = $report['from']->isSameDay($report['to'])
        ? $report['from']->format('D, d M Y')
        : $report['from']->format('d M Y').' – '.$report['to']->format('d M Y');
    $statementUrl = route('pocket-pay.cards.statement', ['card' => $card, 'from' => $from, 'to' => $to]);
    $canAssign = \App\Support\PocketPayAccess::canAssignCards();
    $canReview = \App\Support\PocketPayAccess::canReviewReversals();
    $tabs = [
        'overview' => 'Overview',
        'statement' => 'Statement',
        'purchases' => 'Purchases',
        'topups' => 'Top-ups',
        'alerts' => 'Parent alerts',
    ];
    $statusPill = [
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'sent' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'assigned' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'processing' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'queued' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'inactive' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'reversed' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'failed' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'blocked' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'locked' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'retired' => 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-gray-500/10 dark:text-gray-400',
    ];
    $pill = fn (?string $state) => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.($statusPill[$state] ?? $statusPill['retired']);
    $alertLabels = [
        'pocket_pay.card_activated' => 'Card activated',
        'pocket_pay.card_blocked' => 'Card deactivated',
        'pocket_pay.card_unblocked' => 'Card activated again',
        'pocket_pay.card_locked' => 'Card locked',
        'pocket_pay.card_unlocked' => 'Card unlocked',
        'pocket_pay.pin_reset' => 'PIN reset',
        'pocket_pay.limit_changed' => 'Daily limit changed',
        'pocket_pay.sale' => 'Purchase',
        'pocket_pay.reversal' => 'Reversal',
        'pocket_pay.topup' => 'Top-up',
    ];
    $th = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $thRight = 'px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $td = 'px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap';
    $tdRight = 'px-4 py-3 text-sm text-right tabular-nums whitespace-nowrap text-gray-700 dark:text-gray-300';
    $box = 'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800';
    $initials = str($student?->full_name ?? '?')->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="{{ $box }} overflow-hidden">
        <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
                @if ($studentInfo['photo_url'] ?? null)
                    <img src="{{ $studentInfo['photo_url'] }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-white shadow dark:ring-gray-800">
                @else
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xl font-bold text-white shadow-sm">{{ $initials }}</div>
                @endif
                <div>
                    <nav class="text-xs text-gray-500 dark:text-gray-400">
                        <a href="{{ route('pocket-pay.cards.index') }}" class="hover:text-blue-600">Pocket Pay</a>
                        <span class="mx-1">/</span>
                        <a href="{{ route('pocket-pay.cards.index') }}" class="hover:text-blue-600">Cards</a>
                    </nav>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $student?->full_name ?? 'Unassigned student' }}</h1>
                        <span class="{{ $pill($card->status) }}">{{ \App\Models\PocketPayCard::statusLabel($card->status) }}</span>
                        @if ($card->isLocked())
                            <span class="{{ $pill('locked') }}">PIN locked</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $student?->classRoom?->name ?? 'No class' }}
                        @if ($student?->student_id)
                            <span class="mx-1.5">·</span> Adm. {{ $student->student_id }}
                        @endif
                        <span class="mx-1.5">·</span> {{ $card->business?->name }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{ $this->unlockAction }}
                {{ $this->limitAction }}
                {{ $this->changeNumberAction }}
                {{ $this->deactivateAction }}
                {{ $this->activateAction }}
                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                        <x-heroicon-o-document-arrow-down class="h-4 w-4" />
                        Downloads
                        <x-heroicon-m-chevron-down class="h-4 w-4" />
                    </button>
                    <div x-show="open" x-transition x-cloak
                        class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <a href="{{ $statementUrl }}" target="_blank" class="block rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Card statement (PDF)</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $periodLabel }}: balances, every movement and purchases</span>
                        </a>
                        @if ($canAssign)
                            <a href="{{ route('pocket-pay.cards.qr', $card) }}" class="block rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Card QR code (SVG)</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">For printing a replacement card</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Card strip --}}
        <div class="grid gap-4 border-t border-gray-100 bg-gray-50/70 px-6 py-4 text-sm dark:border-gray-700 dark:bg-gray-900/40 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Card number</p>
                <p class="mt-0.5 flex items-center gap-2 font-mono font-semibold text-gray-900 dark:text-white" x-data="{ copied: false }">
                    {{ \App\Models\PocketPayCardNumber::format($card->card_number) }}
                    <button type="button" class="text-gray-400 hover:text-blue-600" title="Copy"
                        @click="navigator.clipboard.writeText('{{ $card->card_number }}'); copied = true; setTimeout(() => copied = false, 1500)">
                        <x-heroicon-o-clipboard-document class="h-4 w-4" x-show="!copied" />
                        <x-heroicon-o-check class="h-4 w-4 text-emerald-600" x-show="copied" x-cloak />
                    </button>
                </p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Parent / guardian</p>
                @if ($parent)
                    <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">{{ $parent->full_name }}</p>
                    <p class="text-gray-500 dark:text-gray-400">{{ $parent->phone ?: 'No phone number' }}</p>
                @else
                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">No parent linked</p>
                @endif
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Daily limit</p>
                <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">{{ $card->daily_limit !== null ? $money($card->daily_limit) : 'No limit' }}</p>
                @if ($card->daily_limit !== null)
                    <p class="text-gray-500 dark:text-gray-400">{{ $money($summary['remaining_today']) }} left today</p>
                @endif
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Activated</p>
                <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">{{ $card->activated_at ? $local($card->activated_at, 'd M Y') : 'Not yet' }}</p>
                @if ($card->activatedBy)
                    <p class="text-gray-500 dark:text-gray-400">{{ $actor($card->activatedBy) }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Warnings --}}
    @if ($card->isLocked())
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            <x-heroicon-o-lock-closed class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Locked after too many wrong PINs until <strong>{{ $local($card->locked_until, 'H:i, d M') }}</strong>. Use <strong>Unlock PIN</strong> to let the student try again now, or an attendant or parent can reset the PIN.</p>
        </div>
    @endif
    @if ($card->status === \App\Models\PocketPayCard::STATUS_INACTIVE)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <x-heroicon-o-information-circle class="mt-0.5 h-5 w-5 shrink-0" />
            <p>This card is not activated yet. The student sets a 4-digit PIN at a canteen (Quisat Attendant app) or a parent activates it in the Quisat app.</p>
        </div>
    @endif
    @if ($parent && ! $parent->phone)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <x-heroicon-o-device-phone-mobile class="mt-0.5 h-5 w-5 shrink-0" />
            <p>The parent has no phone number, so no SMS alerts are sent for this card. Add one on the parent's profile.</p>
        </div>
    @endif

    {{-- Period --}}
    <div class="{{ $box }} flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Period</p>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $periodLabel }} <span class="font-normal text-gray-500">· {{ $tz }}</span></p>
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
            ['Balance', $money($summary['balance']), $summary['pending_topups'] > 0 ? $money($summary['pending_topups']).' top-up pending' : 'Available now', 'heroicon-o-wallet', 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10'],
            ['Spent', $money($summary['spent']), number_format($summary['purchases']).' '.str('purchase')->plural($summary['purchases']).' in period', 'heroicon-o-shopping-bag', 'text-blue-600 bg-blue-50 dark:bg-blue-500/10'],
            ['Topped up', $money($summary['topups']), number_format($summary['topup_count']).' mobile money '.str('top-up')->plural($summary['topup_count']), 'heroicon-o-arrow-down-tray', 'text-teal-600 bg-teal-50 dark:bg-teal-500/10'],
            ['Spent today', $money($summary['spent_today']), $card->daily_limit !== null ? 'of '.$money($card->daily_limit).' limit' : 'No daily limit', 'heroicon-o-clock', 'text-violet-600 bg-violet-50 dark:bg-violet-500/10'],
            ['Average purchase', $money($summary['average_purchase']), 'In period', 'heroicon-o-receipt-percent', 'text-gray-600 bg-gray-100 dark:bg-gray-500/10'],
            ['Opening balance', $money($summary['opening']), 'Closing '.$money($summary['closing']), 'heroicon-o-scale', 'text-gray-600 bg-gray-100 dark:bg-gray-500/10'],
            ['Refunds', $money($summary['refunds']), number_format($summary['reversed_count']).' reversed '.str('sale')->plural($summary['reversed_count']), 'heroicon-o-arrow-uturn-left', 'text-rose-600 bg-rose-50 dark:bg-rose-500/10'],
            ['Lifetime spend', $money($summary['lifetime_spent']), number_format($summary['lifetime_purchases']).' purchases since issue', 'heroicon-o-chart-bar', 'text-amber-600 bg-amber-50 dark:bg-amber-500/10'],
        ] as [$label, $value, $hint, $icon, $tone])
            <div class="{{ $box }} p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <span class="rounded-lg p-1.5 {{ $tone }}"><x-dynamic-component :component="$icon" class="h-4 w-4" /></span>
                </div>
                <p class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $value }}</p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    @if ($summary['pending_reversals'] > 0 && $tab !== 'purchases')
        <button type="button" wire:click="setTab('purchases')"
            class="flex w-full items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-left text-sm text-amber-800 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <span><strong>{{ $summary['pending_reversals'] }}</strong> reversal {{ str('request')->plural($summary['pending_reversals']) }} on this card waiting for {{ $canReview ? 'your' : 'Quisat' }} approval.</span>
            <span class="font-semibold">{{ $canReview ? 'Review' : 'View' }} →</span>
        </button>
    @endif

    {{-- Tabs --}}
    <div class="{{ $box }}">
        <div class="overflow-x-auto border-b border-gray-200 dark:border-gray-700">
            <nav class="flex min-w-max gap-1 px-4">
                @foreach ($tabs as $key => $label)
                    <button type="button" wire:click="setTab('{{ $key }}')" @class([
                        '-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-3.5 text-sm font-medium',
                        'border-blue-600 text-blue-600 dark:text-blue-400' => $tab === $key,
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $key,
                    ])>
                        {{ $label }}
                        @if ($key === 'purchases' && $summary['pending_reversals'] > 0)
                            <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-bold text-amber-950">{{ $summary['pending_reversals'] }}</span>
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
                    $bestDay = collect($daily)->sortByDesc('amount')->first();
                @endphp
                <div class="grid gap-6 p-6 xl:grid-cols-3">
                    <div class="xl:col-span-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daily spending</h3>
                            @if ($bestDay && $bestDay['amount'] > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400">Highest: {{ $bestDay['date']->format('D d M') }} · {{ $money($bestDay['amount']) }}</p>
                            @endif
                        </div>
                        <div class="mt-4 flex h-56 items-end gap-px border-b border-l border-gray-200 pl-px dark:border-gray-700">
                            @foreach ($daily as $day)
                                <div class="group relative flex h-full flex-1 items-end">
                                    <div class="w-full rounded-t bg-blue-500/80 transition group-hover:bg-blue-600 dark:bg-blue-500/70"
                                        style="height: {{ $day['amount'] > 0 ? max(2, round($day['amount'] / $maxDay * 100, 1)) : 0 }}%"></div>
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white shadow group-hover:block">
                                        {{ $day['date']->format('D d M') }}<br>{{ $money($day['amount']) }} · {{ $day['count'] }} {{ str('purchase')->plural($day['count']) }}
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
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Balance movement</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Opening balance</dt><dd class="font-medium tabular-nums text-gray-900 dark:text-white">{{ $money($summary['opening']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Top-ups</dt><dd class="tabular-nums text-emerald-600">+ {{ $money($summary['topups']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Refunds</dt><dd class="tabular-nums text-emerald-600">+ {{ $money($summary['refunds']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Purchases</dt><dd class="tabular-nums text-rose-600">− {{ $money($summary['debits']) }}</dd></div>
                            <div class="flex justify-between border-t border-gray-200 pt-3 dark:border-gray-700"><dt class="font-semibold text-gray-900 dark:text-white">Closing balance</dt><dd class="font-bold tabular-nums text-gray-900 dark:text-white">{{ $money($summary['closing']) }}</dd></div>
                        </dl>
                        <button type="button" wire:click="setTab('statement')" class="mt-5 text-sm font-medium text-blue-600 hover:text-blue-800">View full statement →</button>
                    </div>

                    <div class="xl:col-span-3 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between px-5 py-4">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Where the card is used</h3>
                                <button type="button" wire:click="setTab('purchases')" class="text-xs font-medium text-blue-600 hover:text-blue-800">All purchases →</button>
                            </div>
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/40"><tr><th class="{{ $th }}">Canteen</th><th class="{{ $thRight }}">Purchases</th><th class="{{ $thRight }}">Spent</th><th class="{{ $th }}">Last used</th></tr></thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($report['canteens'] as $row)
                                        <tr>
                                            <td class="{{ $td }} font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                            <td class="{{ $tdRight }}">{{ number_format($row['count']) }}</td>
                                            <td class="{{ $tdRight }}">{{ $money($row['amount']) }}</td>
                                            <td class="{{ $td }}">{{ $local($row['last_at'], 'd M, H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No purchases in this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Card details</h3>
                            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <div><dt class="text-gray-500 dark:text-gray-400">Assigned</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $local($card->created_at, 'd M Y') }}{{ $card->assigner ? ' · '.$card->assigner->name : '' }}</dd></div>
                                <div><dt class="text-gray-500 dark:text-gray-400">PIN</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $card->pin ? 'Set' : 'Not set' }}{{ $card->failed_pin_attempts > 0 ? ' · '.$card->failed_pin_attempts.' wrong '.str('attempt')->plural($card->failed_pin_attempts) : '' }}</dd></div>
                                <div><dt class="text-gray-500 dark:text-gray-400">First purchase</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $local($summary['first_purchase_at'], 'd M Y') }}</dd></div>
                                <div><dt class="text-gray-500 dark:text-gray-400">Last purchase</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $local($summary['last_purchase_at']) }}</dd></div>
                            </dl>

                            <h4 class="mt-6 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Card numbers</h4>
                            <ul class="mt-2 divide-y divide-gray-100 text-sm dark:divide-gray-700">
                                @forelse ($numbers as $number)
                                    <li class="flex items-center justify-between py-2">
                                        <span class="font-mono text-gray-900 dark:text-white">{{ \App\Models\PocketPayCardNumber::format($number->number) }}</span>
                                        <span class="flex items-center gap-2 text-xs text-gray-500">
                                            {{ $local($number->assigned_at, 'd M Y') }}
                                            <span class="{{ $pill($number->status) }}">{{ $number->status === 'retired' ? 'Replaced' : 'Current' }}</span>
                                        </span>
                                    </li>
                                @empty
                                    <li class="py-2 font-mono text-gray-900 dark:text-white">{{ \App\Models\PocketPayCardNumber::format($card->card_number) }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Statement --}}
            @if ($tab === 'statement')
                <div class="grid grid-cols-2 gap-px border-b border-gray-200 bg-gray-200 dark:border-gray-700 dark:bg-gray-700 md:grid-cols-4">
                    @foreach ([['Opening balance', $summary['opening'], ''], ['Total credits', $summary['credits'], 'text-emerald-600'], ['Total debits', $summary['debits'], 'text-rose-600'], ['Closing balance', $summary['closing'], '']] as [$label, $value, $tone])
                        <div class="bg-white p-4 dark:bg-gray-800">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                            <p class="mt-1 text-lg font-bold tabular-nums {{ $tone ?: 'text-gray-900 dark:text-white' }}">{{ $money($value) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end p-4">
                    <a href="{{ $statementUrl }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800"><x-heroicon-o-document-arrow-down class="h-4 w-4" /> Statement PDF</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Date</th><th class="{{ $th }}">Description</th><th class="{{ $th }}">Canteen</th><th class="{{ $th }}">Type</th><th class="{{ $thRight }}">Debit</th><th class="{{ $thRight }}">Credit</th><th class="{{ $thRight }}">Balance</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @if ($ledger->onFirstPage())
                                <tr class="bg-blue-50/50 dark:bg-blue-500/5">
                                    <td class="{{ $td }}">{{ $report['from']->format('d M Y') }}</td>
                                    <td class="{{ $td }} font-semibold text-gray-900 dark:text-white" colspan="5">Opening balance</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($summary['opening']) }}</td>
                                </tr>
                            @endif
                            @forelse ($ledger as $entry)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}">{{ $local($entry->created_at) }}</td>
                                    <td class="{{ $td }}">{{ $entry->description }}</td>
                                    <td class="{{ $td }}">{{ $entry->canteen?->name ?? '—' }}</td>
                                    <td class="{{ $td }}"><span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $entry->type === 'sale' ? 'purchase' : $entry->type }}</span></td>
                                    <td class="{{ $tdRight }} text-rose-600">{{ $entry->direction === 'debit' ? number_format($entry->amount) : '' }}</td>
                                    <td class="{{ $tdRight }} text-emerald-600">{{ $entry->direction === 'credit' ? number_format($entry->amount) : '' }}</td>
                                    <td class="{{ $tdRight }} font-medium text-gray-900 dark:text-white">{{ number_format($entry->balance_after) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No movements in this period.</td></tr>
                            @endforelse
                            @if (! $ledger->hasMorePages())
                                <tr class="bg-blue-50/50 dark:bg-blue-500/5">
                                    <td class="{{ $td }}">{{ $report['to']->format('d M Y') }}</td>
                                    <td class="{{ $td }} font-semibold text-gray-900 dark:text-white" colspan="3">Closing balance</td>
                                    <td class="{{ $tdRight }} font-semibold text-rose-600">{{ number_format($summary['debits']) }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-emerald-600">{{ number_format($summary['credits']) }}</td>
                                    <td class="{{ $tdRight }} font-bold text-gray-900 dark:text-white">{{ $money($summary['closing']) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $ledger->links() }}</div>
            @endif

            {{-- Purchases --}}
            @if ($tab === 'purchases')
                <div class="flex items-center justify-between p-4">
                    <select wire:model.live="status" class="rounded-lg border-gray-300 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        <option value="">All purchases</option>
                        <option value="completed">Completed</option>
                        <option value="reversed">Reversed</option>
                    </select>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $periodLabel }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Date & time</th><th class="{{ $th }}">Receipt</th><th class="{{ $th }}">Canteen</th><th class="{{ $th }}">Attendant</th><th class="{{ $th }}">Item</th><th class="{{ $thRight }}">Amount</th><th class="{{ $th }}">Status</th><th class="{{ $thRight }}"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($sales as $sale)
                                <tr @class(['hover:bg-gray-50/60 dark:hover:bg-gray-900/30', 'bg-amber-50/40 dark:bg-amber-500/5' => $sale->reversal_status === 'pending'])>
                                    <td class="{{ $td }}">{{ $local($sale->created_at) }}</td>
                                    <td class="{{ $td }} font-mono text-xs">{{ $sale->receipt_number }}</td>
                                    <td class="{{ $td }}">{{ $sale->canteen?->name ?? '—' }}</td>
                                    <td class="{{ $td }}">{{ $sale->attendant?->name ?? '—' }}</td>
                                    <td class="{{ $td }} max-w-[14rem] truncate">{{ $sale->description ?: '—' }}</td>
                                    <td @class([$tdRight, 'font-semibold text-gray-900 dark:text-white', 'line-through text-gray-400' => $sale->status === 'reversed'])>{{ $money($sale->amount) }}</td>
                                    <td class="{{ $td }}">
                                        <span class="{{ $pill($sale->status) }}">{{ ucfirst($sale->status) }}</span>
                                        @if ($sale->reversal_status)
                                            <span class="block pt-1 text-xs text-gray-500">
                                                Reversal {{ $sale->reversal_status }}{{ $sale->reversal_reason ? ': '.$sale->reversal_reason : '' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="{{ $tdRight }}">
                                        @if ($sale->reversal_status === 'pending' && $canReview)
                                            <div class="flex justify-end gap-2">
                                                {{ ($this->approveReversalAction)(['sale' => $sale->id]) }}
                                                {{ ($this->rejectReversalAction)(['sale' => $sale->id]) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">No purchases in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $sales->links() }}</div>
            @endif

            {{-- Top-ups --}}
            @if ($tab === 'topups')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Started</th><th class="{{ $thRight }}">Amount</th><th class="{{ $th }}">Paid from</th><th class="{{ $th }}">Started by</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Credited</th><th class="{{ $th }}">Payment ref.</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($topups as $topup)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}">{{ $local($topup->created_at) }}</td>
                                    <td class="{{ $tdRight }} font-semibold text-gray-900 dark:text-white">{{ $money($topup->amount) }}</td>
                                    <td class="{{ $td }}">{{ $topup->phone_number }}</td>
                                    <td class="{{ $td }}">{{ $actor($topup->initiatedBy) }}</td>
                                    <td class="{{ $td }}"><span class="{{ $pill($topup->status) }}">{{ ucfirst($topup->status) }}</span></td>
                                    <td class="{{ $td }}">{{ $local($topup->credited_at) }}</td>
                                    <td class="{{ $td }} font-mono text-xs">{{ $topup->paymentCollections->sortByDesc('id')->first()?->reference ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No top-ups in this period. Parents and attendants top up by mobile money.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $topups->links() }}</div>
            @endif

            {{-- Parent alerts --}}
            @if ($tab === 'alerts')
                <div class="p-4 text-sm text-gray-500 dark:text-gray-400">
                    Every SMS sent to the parent about this card, newest first{{ $parent?->phone ? ' · sent to '.$parent->phone : '' }}.
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr><th class="{{ $th }}">Date</th><th class="{{ $th }}">Event</th><th class="{{ $th }}">Message</th><th class="{{ $th }}">To</th><th class="{{ $th }}">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($alerts as $sms)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-900/30">
                                    <td class="{{ $td }}">{{ $local($sms->created_at) }}</td>
                                    <td class="{{ $td }} font-medium text-gray-900 dark:text-white">{{ $alertLabels[$sms->purpose] ?? str($sms->purpose)->after('pocket_pay.')->replace('_', ' ')->ucfirst() }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 min-w-[18rem] max-w-xl">{{ $sms->message }}</td>
                                    <td class="{{ $td }}">{{ $sms->recipient }}</td>
                                    <td class="{{ $td }}">
                                        <span class="{{ $pill($sms->status) }}">{{ ucfirst($sms->status) }}</span>
                                        @if ($sms->error)
                                            <span class="block pt-1 text-xs text-rose-600">{{ str($sms->error)->limit(60) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">No SMS alerts yet{{ $parent?->phone ? '' : ': the parent has no phone number' }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $alerts->links() }}</div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</div>
