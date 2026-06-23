        <div class="md:col-span-2 border-t border-gray-200 pt-6 mt-2">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Promotion, sales &amp; discounts</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label class="inline-flex items-center text-sm text-gray-700">
                <input type="checkbox" name="is_on_sale" value="1"
                    @checked(old('is_on_sale', $product->is_on_sale ?? false))
                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <span class="ml-2 font-medium">Put this product on sale / promotion</span>
            </label>
        </div>

        <div>
            <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">Sale price</label>
            <input type="number" name="sale_price" id="sale_price"
                value="{{ old('sale_price', $product->sale_price ?? '') }}"
                step="0.01" min="0"
                placeholder="Discounted price"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-xs text-gray-500">Must be lower than the regular price.</p>
            @error('sale_price')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="promotion_label" class="block text-sm font-medium text-gray-700 mb-2">Promotion label</label>
            <input type="text" name="promotion_label" id="promotion_label"
                value="{{ old('promotion_label', $product->promotion_label ?? '') }}"
                placeholder="e.g. Summer Sale, 20% OFF"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('promotion_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="promotion_starts_at" class="block text-sm font-medium text-gray-700 mb-2">Promotion starts</label>
            <input type="datetime-local" name="promotion_starts_at" id="promotion_starts_at"
                value="{{ old('promotion_starts_at', optional($product->promotion_starts_at)->format('Y-m-d\TH:i')) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('promotion_starts_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="promotion_ends_at" class="block text-sm font-medium text-gray-700 mb-2">Promotion ends</label>
            <input type="datetime-local" name="promotion_ends_at" id="promotion_ends_at"
                value="{{ old('promotion_ends_at', optional($product->promotion_ends_at)->format('Y-m-d\TH:i')) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('promotion_ends_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
