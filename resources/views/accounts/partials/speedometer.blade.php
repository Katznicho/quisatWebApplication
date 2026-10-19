@php
    $graphData = $graphData ?? [
        'available' => false,
        'percent' => 0,
        'value' => 0,
        'max' => 0,
        'remaining' => 0,
        'label' => 'Account usage',
    ];

    $percent = max(0, min(100, (float) ($graphData['percent'] ?? 0)));
    $value = (float) ($graphData['value'] ?? 0);
    $max = (float) ($graphData['max'] ?? 0);
    $remaining = (float) ($graphData['remaining'] ?? max(0, $max - $value));
    $needleAngle = -90 + ($percent * 1.8);

    $status = match (true) {
        $percent >= 85 => ['label' => 'Critical', 'text' => 'text-red-700', 'bg' => 'bg-red-50', 'ring' => 'ring-red-100', 'dot' => 'bg-red-500', 'needle' => '#d1111b'],
        $percent >= 60 => ['label' => 'Warning', 'text' => 'text-amber-700', 'bg' => 'bg-amber-50', 'ring' => 'ring-amber-100', 'dot' => 'bg-amber-500', 'needle' => '#d97706'],
        default => ['label' => 'Healthy', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-100', 'dot' => 'bg-emerald-500', 'needle' => '#322f90'],
    };
@endphp

<div class="w-full rounded-2xl border border-gray-100 bg-gradient-to-r from-slate-50 via-white to-white px-5 py-4 shadow-sm">
    <div class="flex items-center justify-between gap-3 mb-3">
        <div>
            <p class="text-[11px] font-semibold tracking-[0.18em] text-gray-400 uppercase">Usage meter</p>
            <p class="mt-0.5 text-sm font-semibold text-[#322f90]">{{ $graphData['label'] ?? 'Account usage' }}</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $status['bg'] }} {{ $status['text'] }} ring-1 {{ $status['ring'] }}">
            <span class="h-1.5 w-1.5 rounded-full mr-1.5 {{ $status['dot'] }}"></span>
            {{ $status['label'] }}
        </span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
        <div class="relative mx-auto sm:mx-0 w-[168px] h-[108px] shrink-0">
            <svg viewBox="0 20 240 130" class="w-full h-full" role="img" aria-label="Account usage {{ number_format($percent, 1) }} percent">
                <defs>
                    <linearGradient id="gaugeTrack" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#22c55e"/>
                        <stop offset="55%" stop-color="#eab308"/>
                        <stop offset="100%" stop-color="#d1111b"/>
                    </linearGradient>
                </defs>

                <path d="M28 120 A92 92 0 0 1 212 120" fill="none" stroke="#eef2f7" stroke-width="18" stroke-linecap="round"></path>
                <path d="M28 120 A92 92 0 0 1 212 120" fill="none" stroke="url(#gaugeTrack)" stroke-width="18" stroke-linecap="round"></path>
                <path d="M28 120 A92 92 0 0 1 212 120" fill="none" stroke="#ffffff" stroke-width="6" opacity="0.35" stroke-linecap="round"></path>

                @for ($i = 0; $i <= 10; $i++)
                    @php
                        $tickAngle = deg2rad(-180 + ($i * 18));
                        $inner = $i % 5 === 0 ? 78 : 84;
                        $outer = 92;
                        $cx = 120;
                        $cy = 120;
                        $x1 = $cx + cos($tickAngle) * $inner;
                        $y1 = $cy + sin($tickAngle) * $inner;
                        $x2 = $cx + cos($tickAngle) * $outer;
                        $y2 = $cy + sin($tickAngle) * $outer;
                    @endphp
                    <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="#ffffff" stroke-width="{{ $i % 5 === 0 ? 2 : 1 }}" stroke-linecap="round"></line>
                @endfor

                <text x="28" y="142" text-anchor="middle" font-size="11" fill="#94a3b8" font-weight="600">0</text>
                <text x="120" y="38" text-anchor="middle" font-size="11" fill="#94a3b8" font-weight="600">50</text>
                <text x="212" y="142" text-anchor="middle" font-size="11" fill="#94a3b8" font-weight="600">100</text>

                <g transform="rotate({{ $needleAngle }} 120 120)">
                    <polygon points="120,36 124,120 116,120" fill="{{ $status['needle'] }}"></polygon>
                    <circle cx="120" cy="120" r="8" fill="{{ $status['needle'] }}"></circle>
                    <circle cx="120" cy="120" r="3.5" fill="#ffffff"></circle>
                </g>
            </svg>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-3xl font-bold tracking-tight text-[#322f90] leading-none">{{ number_format($percent, 1) }}%</p>
                    <p class="mt-1 text-xs text-gray-400">of allocated limit</p>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-3 gap-2">
                <div class="rounded-xl bg-white border border-gray-100 px-3 py-2">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Used</p>
                    <p class="mt-0.5 text-sm font-semibold text-gray-800 tabular-nums">{{ number_format($value, 2) }}</p>
                </div>
                <div class="rounded-xl bg-white border border-gray-100 px-3 py-2">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Remaining</p>
                    <p class="mt-0.5 text-sm font-semibold text-gray-800 tabular-nums">{{ number_format($remaining, 2) }}</p>
                </div>
                <div class="rounded-xl bg-white border border-gray-100 px-3 py-2">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Limit</p>
                    <p class="mt-0.5 text-sm font-semibold text-gray-800 tabular-nums">{{ number_format($max, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
