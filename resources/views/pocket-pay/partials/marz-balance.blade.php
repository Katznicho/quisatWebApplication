@php
    $account = $gateway ?? $settings;
    $money = $money ?? fn ($amount) => 'UGX '.number_format((float) $amount, 2);
@endphp
@if (! $account->hasPayCredentials())
    <p class="text-sm text-slate-500">Save the API key and secret to connect this account.</p>
    <a href="{{ route('pocket-pay.settings.edit') }}" class="mt-2 inline-flex text-sm font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 dark:text-white">Open account settings</a>
@else
    @if ($marzBalance && ($marzBalance['success'] ?? false))
        <p class="text-2xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ $money($marzBalance['available'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-slate-500">Mobile money{{ ! empty($marzBalance['business_name']) ? ' · '.$marzBalance['business_name'] : '' }}</p>
    @elseif ($marzBalance)
        <p class="text-sm text-rose-700">{{ $marzBalance['message'] ?? 'Marz Pay did not return a balance.' }}</p>
    @else
        <p class="text-sm text-slate-500">The keys are saved. Pull the live wallet when you want the current balance.</p>
    @endif
    <a href="{{ $refreshUrl }}" class="mt-3 inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100">Pull balance</a>
@endif
