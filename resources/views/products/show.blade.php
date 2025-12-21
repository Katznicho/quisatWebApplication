<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('products.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">← Back to Products</a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $product->name }}
                </h2>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        @php
                            $primaryImage = $product->images->where('is_primary', true)->first();
                            $additionalImages = $product->images->where('is_primary', false)->sortBy('sort_order');
                        @endphp
                        
                        @if($primaryImage)
                            <div class="mb-4">
                                <img src="{{ Storage::url($primaryImage->image_url) }}" alt="{{ $product->name }}" class="w-full rounded-lg" id="mainImage">
                            </div>
                        @elseif($product->image_url)
                            <div class="mb-4">
                                <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" class="w-full rounded-lg" id="mainImage">
                            </div>
                        @else
                            <div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif

                        @if($additionalImages->count() > 0 || $product->images->count() > 1)
                            <div class="grid grid-cols-4 gap-2">
                                @if($primaryImage)
                                    <div class="cursor-pointer border-2 border-blue-500 rounded" onclick="changeMainImage('{{ Storage::url($primaryImage->image_url) }}')">
                                        <img src="{{ Storage::url($primaryImage->image_url) }}" alt="Thumbnail" class="w-full h-20 object-cover rounded">
                                    </div>
                                @endif
                                @foreach($additionalImages as $image)
                                    <div class="cursor-pointer border-2 border-gray-300 rounded hover:border-blue-500" onclick="changeMainImage('{{ Storage::url($image->image_url) }}')">
                                        <img src="{{ Storage::url($image->image_url) }}" alt="Thumbnail" class="w-full h-20 object-cover rounded">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold mb-2">{{ $product->name }}</h3>
                        <p class="text-3xl font-bold text-blue-600 mb-4">UGX {{ number_format($product->price, 2) }}</p>
                        <div class="space-y-2 mb-4">
                            <p><span class="font-medium">Category:</span> {{ $product->category ?? 'N/A' }}</p>
                            <p><span class="font-medium">SKU:</span> {{ $product->sku ?? 'N/A' }}</p>
                            <p><span class="font-medium">Stock:</span> {{ $product->stock_quantity }} units</p>
                            <p><span class="font-medium">Status:</span> 
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($product->status == 'active') bg-green-100 text-green-800
                                    @elseif($product->status == 'inactive') bg-gray-100 text-gray-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $product->status)) }}
                                </span>
                            </p>
                        </div>
                        <p class="text-gray-600 mb-6">{{ $product->description ?? 'No description available.' }}</p>
                        <div class="flex space-x-4">
                            <a href="{{ route('products.edit', $product) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Edit</a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeMainImage(imageUrl) {
            document.getElementById('mainImage').src = imageUrl;
            // Update active thumbnail border
            document.querySelectorAll('.grid.grid-cols-4 > div').forEach(div => {
                div.classList.remove('border-blue-500');
                div.classList.add('border-gray-300');
            });
            event.currentTarget.classList.remove('border-gray-300');
            event.currentTarget.classList.add('border-blue-500');
        }
    </script>
</x-app-layout>

