<div class="bg-[#f3f5f8] pb-16 dark:bg-gray-950">
    <div class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('pocket-pay.partials.nav')
        </div>
    </div>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</div>
