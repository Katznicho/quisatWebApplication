<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Add New Product') }}
                </h2>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Enter product name (e.g., Kids Toy Set)" required class="w-full border rounded-lg px-4 py-2">
                            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="4" placeholder="Enter product description (e.g., High-quality educational toy set for children aged 3-8)" class="w-full border rounded-lg px-4 py-2">{{ old('description') }}</textarea>
                            @error('description')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Price (UGX) *</label>
                                <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" placeholder="0.00" required class="w-full border rounded-lg px-4 py-2">
                                @error('price')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0" placeholder="0" required class="w-full border rounded-lg px-4 py-2">
                                @error('stock_quantity')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g., Toys, Clothing, Books" class="w-full border rounded-lg px-4 py-2">
                                @error('category')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                                <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Leave blank to auto-generate" class="w-full border rounded-lg px-4 py-2">
                                @error('sku')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Main Product Image *</label>
                            <p class="text-xs text-gray-500 mb-2">Upload the primary/big image for this product</p>
                            <input type="file" name="primary_image" accept="image/*" class="w-full border rounded-lg px-4 py-2">
                            @error('primary_image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Images (Optional)</label>
                            <p class="text-xs text-gray-500 mb-2">Upload up to 4 additional images</p>
                            <input type="file" name="additional_images[]" accept="image/*" multiple class="w-full border rounded-lg px-4 py-2">
                            <p class="text-xs text-gray-400 mt-1">You can select multiple images at once (max 4)</p>
                            @error('additional_images.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                <select name="status" required class="w-full border rounded-lg px-4 py-2">
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="out_of_stock" {{ old('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                </select>
                                @error('status')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-center pt-8">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', true) ? 'checked' : '' }} class="mr-2">
                                    <span class="text-sm font-medium text-gray-700">Available for purchase</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex space-x-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Create Product</button>
                            <a href="{{ route('products.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

