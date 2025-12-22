<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsMart - Shop for Kids</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <h1 class="text-3xl font-bold text-gray-900">KidsMart</h1>
                <p class="text-gray-600 mt-1">Shop for your little ones</p>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Categories Filter -->
            @if($categories->count() > 0)
            <div class="mb-6">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('products.public') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg">All</a>
                    @foreach($categories as $category)
                        <a href="{{ route('products.public', ['category' => $category]) }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">{{ $category }}</a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Products Grid -->
            @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($products as $product)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
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
                                @if($product->category)
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded mb-2 inline-block">{{ $product->category }}</span>
                                @endif
                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $product->description }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-xl font-bold text-blue-600">UGX {{ number_format($product->price, 2) }}</span>
                                    <span class="text-sm text-gray-500">{{ $product->stock_quantity }} in stock</span>
                                </div>
                                @if($product->business)
                                    <p class="text-xs text-gray-500 mt-2">Seller: {{ $product->business->name }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">No products available at the moment.</p>
                </div>
            @endif
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white mt-12 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p>&copy; {{ date('Y') }} KidsMart. All rights reserved.</p>
                <p class="text-sm text-gray-400 mt-2">View products in the Quisat mobile app to place orders.</p>
            </div>
        </footer>
    </div>
</body>
</html>



