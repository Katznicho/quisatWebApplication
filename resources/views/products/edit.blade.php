@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Product</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Update product information</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $product->name) }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Price (UGX) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="price" 
                           id="price" 
                           value="{{ old('price', $product->price) }}"
                           step="0.01"
                           min="0"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        Category
                    </label>
                    <input type="text" 
                           name="category" 
                           id="category" 
                           value="{{ old('category', $product->category) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stock Quantity -->
                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Stock Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="stock_quantity" 
                           id="stock_quantity" 
                           value="{{ old('stock_quantity', $product->stock_quantity) }}"
                           min="0"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('stock_quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sizes -->
                <div>
                    <label for="sizes" class="block text-sm font-medium text-gray-700 mb-2">
                        Sizes (comma-separated)
                    </label>
                    <input type="text" 
                           name="sizes" 
                           id="sizes" 
                           value="{{ old('sizes', is_array($product->sizes) ? implode(', ', $product->sizes) : '') }}"
                           placeholder="e.g., S, M, L, XL"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Enter sizes separated by commas</p>
                    @error('sizes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Available -->
                <div>
                    <label for="is_available" class="block text-sm font-medium text-gray-700 mb-2">
                        Availability
                    </label>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_available" 
                               id="is_available" 
                               value="1"
                               {{ old('is_available', $product->is_available) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_available" class="ml-2 block text-sm text-gray-700">
                            Product is available
                        </label>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select name="status" 
                            id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" {{ old('status', $product->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Main Image -->
                @if($product->image_path)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Main Image
                    </label>
                    <img src="{{ Storage::url($product->image_path) }}" 
                         alt="{{ $product->name }}" 
                         class="h-32 w-32 object-cover rounded border">
                </div>
                @endif

                <!-- Main Image -->
                <div class="md:col-span-2">
                    <label for="image_path" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $product->image_path ? 'Replace Main Product Image' : 'Main Product Image' }}
                    </label>
                    <input type="file" 
                           name="image_path" 
                           id="image_path" 
                           accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Max file size: 2MB. Allowed formats: JPEG, PNG, JPG, GIF, WEBP</p>
                    @error('image_path')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Additional Images -->
                @if($product->images->count() > 0)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Additional Images
                    </label>
                    <div class="flex flex-wrap gap-4">
                        @foreach($product->images as $image)
                            <div class="relative">
                                <img src="{{ Storage::url($image->image_url) }}" 
                                     alt="Product image" 
                                     class="h-24 w-24 object-cover rounded border">
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Additional Images -->
                <div class="md:col-span-2">
                    <label for="images" class="block text-sm font-medium text-gray-700 mb-2">
                        Add More Images
                    </label>
                    <input type="file" 
                           name="images[]" 
                           id="images" 
                           accept="image/*"
                           multiple
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">You can select multiple images. Max file size: 2MB each.</p>
                    @error('images.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('products.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
