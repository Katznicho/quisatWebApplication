<div>
    @if($scopedPatient)
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Fees for {{ $scopedPatient->full_name }}</h3>
            <p class="mt-1 text-sm text-slate-500">Create and track clinic bills for this patient. Parents see payable items in the Quisat Fees screen.</p>
        </div>
    @else
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Clinic billing</h3>
            <p class="mt-1 text-sm text-slate-500">Pick a registered patient and create fees just like school billing.</p>
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Billed</p>
            <p class="text-lg font-semibold">{{ $currency }} {{ number_format($stats['billed'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Paid</p>
            <p class="text-lg font-semibold text-green-700">{{ $currency }} {{ number_format($stats['paid'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Pending</p>
            <p class="text-lg font-semibold text-amber-700">{{ $currency }} {{ number_format($stats['pending'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Arrears</p>
            <p class="text-lg font-semibold text-red-700">{{ $currency }} {{ number_format($stats['arrears'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Credits / overpay</p>
            <p class="text-lg font-semibold">{{ $currency }} {{ number_format($stats['credits'], 0) }}</p>
        </div>
    </div>

    {{ $this->table }}
</div>
