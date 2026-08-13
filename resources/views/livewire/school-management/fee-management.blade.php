<div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Billed</p>
            <p class="text-lg font-semibold">UGX {{ number_format($stats['billed'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Paid</p>
            <p class="text-lg font-semibold text-green-700">UGX {{ number_format($stats['paid'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Pending</p>
            <p class="text-lg font-semibold text-amber-700">UGX {{ number_format($stats['pending'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Arrears</p>
            <p class="text-lg font-semibold text-red-700">UGX {{ number_format($stats['arrears'], 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs uppercase text-gray-500">Credits / overpay</p>
            <p class="text-lg font-semibold">UGX {{ number_format($stats['credits'], 0) }}</p>
        </div>
    </div>

    {{ $this->table }}
</div>
