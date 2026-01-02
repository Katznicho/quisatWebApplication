<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ __('KidsMart Products') }}
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your products for KidsMart</p>
                    </div>
                    <a href="{{ route('products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Product</span>
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Products Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                        @foreach($products as $product)
                            <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                                @if($product->image_url)
                                    <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg mb-2">{{ $product->name }}</h3>
                                    <p class="text-gray-600 text-sm mb-2 line-clamp-2">{{ $product->description }}</p>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-lg font-bold text-blue-600">UGX {{ number_format($product->price, 2) }}</span>
                                        <span class="text-sm text-gray-500">Stock: {{ $product->stock_quantity }}</span>
                                    </div>
                                    <div class="flex space-x-2 mt-4">
                                        <a href="{{ route('products.show', $product) }}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded">View</a>
                                        <a href="{{ route('products.edit', $product) }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white text-center py-2 rounded">Edit</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-6 py-4">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <p class="text-gray-500 mb-4">No products found. Create your first product!</p>
                        <a href="{{ route('products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-block">Add Product</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>








