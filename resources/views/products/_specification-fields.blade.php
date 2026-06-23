@php
    $businessId = auth()->user()->business_id ?? 0;
    $hubValue = $hub ?? old('hub', 'kidz_mart');
    $skuPrefix = $hubValue === 'stationery_hub' ? 'SH' : 'KM';
@endphp

<!-- SKU / Item code -->
<div class="md:col-span-2">
    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
        SKU / Item Code
    </label>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input type="text"
               name="sku"
               id="sku"
               value="{{ old('sku', $product->sku ?? '') }}"
               maxlength="64"
               placeholder="e.g., {{ $skuPrefix }}-{{ $businessId }}-A1B2C3"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono uppercase">
        <button type="button"
                id="generate-sku-btn"
                class="shrink-0 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Generate SKU
        </button>
    </div>
    <p class="mt-1 text-xs text-gray-500">Leave blank to auto-generate when you save. Format: {{ $skuPrefix }}-{{ $businessId }}-XXXXXX</p>
    @error('sku')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Key features -->
<div class="md:col-span-2">
    <label for="key_features" class="block text-sm font-medium text-gray-700 mb-2">
        Key Features
    </label>
    <textarea name="key_features"
              id="key_features"
              rows="4"
              placeholder="List the main features and specifications, one per line.&#10;e.g., Durable hardcover&#10;200 lined pages&#10;A4 size"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('key_features', $product->key_features ?? '') }}</textarea>
    <p class="mt-1 text-xs text-gray-500">Highlight what makes this product stand out (materials, size, durability, etc.)</p>
    @error('key_features')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- What's in the box -->
<div class="md:col-span-2">
    <label for="whats_in_box" class="block text-sm font-medium text-gray-700 mb-2">
        What's in the Box
    </label>
    <textarea name="whats_in_box"
              id="whats_in_box"
              rows="3"
              placeholder="List everything included with the product, one item per line.&#10;e.g., 1 x notebook&#10;2 x pencils&#10;1 x eraser"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('whats_in_box', $product->whats_in_box ?? '') }}</textarea>
    @error('whats_in_box')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('generate-sku-btn');
            const input = document.getElementById('sku');
            if (!btn || !input) return;

            const prefix = @json($skuPrefix);
            const businessId = @json($businessId);
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

            btn.addEventListener('click', function () {
                let suffix = '';
                for (let i = 0; i < 6; i++) {
                    suffix += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                input.value = `${prefix}-${businessId}-${suffix}`;
            });
        });
    </script>
    @endpush
@endonce
