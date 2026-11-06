<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Quisat</p>
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Pocket Pay</h2>
        <p class="mt-1 max-w-xl text-sm text-slate-500 dark:text-slate-400">Student cards, canteen floats, and the Pocket Pay Marz Pay account.</p>
    </div>
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
                'pocket-pay.reports' => request()->routeIs('pocket-pay.reports'),
                'pocket-pay.cards.index' => request()->routeIs('pocket-pay.cards.*'),
                'pocket-pay.settings.edit' => request()->routeIs('pocket-pay.settings.*'),
                default => request()->routeIs($route),
            };
        };
    @endphp
    <nav class="flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1 text-sm font-medium dark:bg-slate-900">
        @foreach ($links as $route => $label)
            <a href="{{ route($route) }}" @class([
                'rounded-lg px-3 py-1.5',
                'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' => $isCurrent($route),
                'text-slate-600 hover:text-slate-900 dark:text-slate-400' => ! $isCurrent($route),
            ])>
                {{ $label }}
                @if ($route === 'pocket-pay.reversals.index' && $pendingReversals > 0)
                    <span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-bold text-amber-950">{{ $pendingReversals }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
