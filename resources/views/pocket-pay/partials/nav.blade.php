@php
    $links = array_filter([
        'pocket-pay.overview' => \App\Support\PocketPayAccess::canAssignCards() ? 'Overview' : null,
        'pocket-pay.reports' => \App\Support\PocketPayAccess::canAssignCards() ? 'Reports' : null,
        'pocket-pay.canteens.index' => 'Canteens',
        'pocket-pay.cards.index' => 'Cards',
        'pocket-pay.card-numbers.index' => \App\Support\PocketPayAccess::canAssignCards() ? 'Card numbers' : null,
        'pocket-pay.reversals.index' => \App\Support\PocketPayAccess::canReviewReversals() ? 'Reversals' : null,
        'pocket-pay.settings.edit' => \App\Support\PocketPayAccess::canAssignCards() ? 'Marz Pay' : null,
    ]);
    $isCurrent = function (string $route): bool {
        return match ($route) {
            'pocket-pay.canteens.index' => request()->routeIs('pocket-pay.canteens.*'),
            'pocket-pay.reports' => request()->routeIs('pocket-pay.reports*'),
            'pocket-pay.cards.index' => request()->routeIs('pocket-pay.cards.*'),
            'pocket-pay.settings.edit' => request()->routeIs('pocket-pay.settings.*'),
            'pocket-pay.overview' => request()->routeIs('pocket-pay.overview', 'pocket-pay.schools.show'),
            default => request()->routeIs($route),
        };
    };
    $intro = match (true) {
        request()->routeIs('pocket-pay.schools.show') => 'Cards, canteens, sales, and top-ups for this school.',
        request()->routeIs('pocket-pay.overview') => 'Choose a school to see its cards, canteens, and movements.',
        request()->routeIs('pocket-pay.reports*') => 'Balances held now, and every movement in the dates you choose.',
        request()->routeIs('pocket-pay.settings.*') => 'The Marz Pay account used for Pocket Pay collections and withdrawals.',
        request()->routeIs('pocket-pay.reversals.*') => 'Sale reversals waiting for a decision, and the ones already reviewed.',
        default => 'Student cards, canteen floats, and the sales made against them.',
    };
@endphp
<div class="pt-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Pocket Pay</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ request()->routeIs('pocket-pay.reports*') ? 'Money reports' : 'Pocket Pay' }}</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ $intro }}</p>
        </div>
    </div>
    <nav class="-mb-px mt-5 flex gap-1 overflow-x-auto" aria-label="Pocket Pay">
        @foreach ($links as $route => $label)
            <a href="{{ route($route) }}" @class([
                'inline-flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-3 text-sm font-medium',
                'border-slate-950 text-slate-950 dark:border-white dark:text-white' => $isCurrent($route),
                'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800 dark:text-slate-400' => ! $isCurrent($route),
            ])>
                {{ $label }}
                @if ($route === 'pocket-pay.reversals.index' && ($pendingReversals ?? 0) > 0)
                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-900">{{ $pendingReversals }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
