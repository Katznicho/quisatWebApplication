@php
    $hubs = $availableHubs ?? [];
    $activeHub = $hub ?? \App\Support\StationeryHub::KIDZ_MART;
    $routeName = $routeName ?? 'products.index';
@endphp

@if (count($hubs) > 1)
    <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-4">
        @foreach ($hubs as $hubKey => $hubLabel)
            <a href="{{ route($routeName, ['hub' => $hubKey]) }}"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $activeHub === $hubKey ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $hubLabel }}
            </a>
        @endforeach
    </div>
@elseif (count($hubs) === 1 && $activeHub === \App\Support\StationeryHub::HUB)
    <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">
        <p class="text-sm font-semibold text-indigo-900">Back to School Stationery Hub</p>
        <p class="text-xs text-indigo-700 mt-1">{{ config('stationery_hub.tagline') }}</p>
    </div>
@endif
