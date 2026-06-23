@if (!empty($isStationery))
    <div class="md:col-span-2 border-t border-gray-200 pt-6 mt-2">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Stationery details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="delivery_days" class="block text-sm font-medium text-gray-700 mb-2">Delivery (days)</label>
                <input type="number" name="delivery_days" id="delivery_days"
                    value="{{ old('delivery_days', $product->delivery_days ?? 3) }}"
                    min="1" max="30"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('delivery_days')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="quality_grade" class="block text-sm font-medium text-gray-700 mb-2">Quality grade</label>
                <select name="quality_grade" id="quality_grade"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select quality</option>
                    @foreach ($qualityOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('quality_grade', $product->quality_grade ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('quality_grade')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Low stock alert at</label>
                <input type="number" name="low_stock_threshold" id="low_stock_threshold"
                    value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 15) }}"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('low_stock_threshold')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
@endif
