<x-app-layout>
    <x-dashboard-layout header="Products">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <!-- Page Header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Product Categories</h1>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($decodedData as $category)
                    <a href="{{ route('categories.products', $category['code']) }}" class="block transition-transform hover:scale-105">
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="aspect-w-16 aspect-h-9">
                                <img src="{{ $category['imageURL'] }}"
                                     alt="{{ $category['description'] }}"
                                     class="w-full h-48 object-cover">
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-[#322f90] mb-2">{{ $category['description'] }}</h3>
                                <p class="text-sm text-gray-600">Code: {{ $category['code'] }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Empty State -->
            @if(empty($decodedData))
                <div class="text-center py-12">
                    <div class="text-gray-500">No categories available</div>
                </div>
            @endif
        </div>
    </x-dashboard-layout>
</x-app-layout>

