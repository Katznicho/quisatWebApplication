<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Quisat Pocket Pay</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Cashless canteens: student cards, canteen floats and attendants.</p>
    </div>
    <nav class="flex gap-1 rounded-lg bg-gray-100 dark:bg-gray-900 p-1 text-sm font-medium">
        @foreach ([
            'pocket-pay.canteens.index' => 'Canteens',
            'pocket-pay.cards.index' => 'Cards',
            'pocket-pay.reversals.index' => 'Reversals',
        ] as $route => $label)
            <a href="{{ route($route) }}" @class([
                'rounded-md px-3 py-1.5',
                'bg-white text-blue-700 shadow-sm dark:bg-gray-800' => request()->routeIs($route) || ($route === 'pocket-pay.canteens.index' && request()->routeIs('pocket-pay.canteens.show')),
                'text-gray-600 hover:text-gray-900 dark:text-gray-400' => ! (request()->routeIs($route) || ($route === 'pocket-pay.canteens.index' && request()->routeIs('pocket-pay.canteens.show'))),
            ])>
                {{ $label }}
                @if ($route === 'pocket-pay.reversals.index' && $pendingReversals > 0)
                    <span class="ml-1 rounded-full bg-yellow-400 px-1.5 py-0.5 text-xs font-bold text-yellow-900">{{ $pendingReversals }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
