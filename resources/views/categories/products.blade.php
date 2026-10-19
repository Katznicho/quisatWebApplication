<x-app-layout>
    <x-dashboard-layout header="Products">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <!-- Page Header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Products in {{ $code }}</h1>
                    <a href="{{ route('categories.index') }}" class="text-[#322f90] hover:underline">← Back to Categories</a>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($decodedData as $product)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="aspect-w-16 aspect-h-9">
                            @if(!empty($product['imageURL']))
                                <img src="{{ $product['imageURL'] }}"
                                     alt="{{ $product['description'] }}"
                                     class="w-full h-48 object-cover">
                            @else
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-[#322f90] mb-2">{{ $product['description'] }}</h3>
                            <p class="text-sm text-gray-600 mb-2">Product Code: {{ $product['productCode'] ?? '-' }}</p>
                            @if(!empty($product['commonName']))
                                <p class="text-sm text-gray-600 mb-2">Common Name: {{ $product['commonName'] }}</p>
                            @endif
                            @if(!empty($product['productDescription']))
                                <p class="text-sm text-gray-600">{{ $product['productDescription'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Empty State -->
            @if(empty($decodedData))
                <div class="text-center py-12">
                    <div class="text-gray-500">No products available in this category</div>
                </div>
            @endif
        </div>
    </x-dashboard-layout>
</x-app-layout>

