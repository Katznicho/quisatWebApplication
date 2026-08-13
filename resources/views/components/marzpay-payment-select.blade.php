@props([
    'name' => 'payment_method',
    'id' => 'payment_method',
    'selected' => old('payment_method', 'mtn_mobile_money'),
    'required' => true,
    'showOther' => true,
    'showBank' => false,
])

<select
    id="{{ $id }}"
    name="{{ $name }}"
    {{ $attributes->merge(['class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white']) }}
    @if($required) required @endif
>
    <option value="mtn_mobile_money" @selected($selected === 'mtn_mobile_money')>MarzPay · MTN Mobile Money</option>
    <option value="airtel_money" @selected($selected === 'airtel_money')>MarzPay · Airtel Money</option>
    <option value="card" @selected($selected === 'card')>MarzPay · Card</option>
    <option value="cash" @selected($selected === 'cash')>Cash</option>
    @if($showBank)
        <option value="bank_transfer" @selected($selected === 'bank_transfer')>Bank transfer (offline)</option>
    @endif
    @if($showOther)
        <option value="other" @selected($selected === 'other')>Other</option>
    @endif
</select>
<p class="mt-1 text-xs text-gray-500">MTN, Airtel, and card payments are processed securely via MarzPay. Approve the prompt on your phone (or complete card checkout in the browser).</p>
