@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    @include('marketplace._hub-tabs', ['availableHubs' => $availableHubs ?? [], 'hub' => $hub ?? 'kidz_mart'])

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $hubLabel ?? 'Kids Mart' }} Products</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your products and inventory</p>
            @if (($lowStockCount ?? 0) > 0)
                <p class="mt-1 text-sm text-amber-700">{{ $lowStockCount }} product(s) at or below low-stock threshold</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('products.bulk-upload-page', ['hub' => $hub ?? 'kidz_mart']) }}"
               class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-file-csv mr-2"></i>Bulk CSV Upload
            </a>
            <a href="{{ route('products.create', ['hub' => $hub ?? 'kidz_mart']) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New Product
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 whitespace-pre-line">
            {{ session('success') }}
        </div>
    @endif

    @if(session('bulk_upload_errors') && count(session('bulk_upload_errors')) > 0)
        <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded mb-4">
            <p class="font-semibold mb-2">Bulk upload notes</p>
            <ul class="list-disc list-inside text-sm max-h-40 overflow-y-auto">
                @foreach(array_slice(session('bulk_upload_errors'), 0, 10) as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @if(count(session('bulk_upload_errors')) > 10)
                    <li>... and {{ count(session('bulk_upload_errors')) - 10 }} more</li>
                @endif
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($product->image_path)
                                <img src="{{ Storage::url($product->image_path) }}" 
                                     alt="{{ $product->name }}" 
                                     class="h-12 w-12 object-cover rounded">
                            @else
                                <div class="h-12 w-12 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                            @if($product->description)
                                <div class="text-sm text-gray-500 truncate max-w-xs">{{ \Illuminate\Support\Str::limit($product->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">
                            {{ $product->sku ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $product->category ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            @if($product->isPromotionActive())
                                <span class="text-gray-400 line-through">{{ $product->business->currency_code ?? 'UGX' }} {{ number_format($product->price, 0) }}</span>
                                <span class="block text-red-600">{{ $product->business->currency_code ?? 'UGX' }} {{ number_format($product->sale_price, 0) }}</span>
                                @if($product->promotion_label)
                                    <span class="text-xs text-amber-700">{{ $product->promotion_label }}</span>
                                @endif
                            @else
                                {{ $product->business->currency_code ?? 'UGX' }} {{ number_format($product->price, 0) }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 {{ $product->isLowStock() ? 'text-amber-700 font-semibold' : '' }}">{{ $product->stock_quantity }}</span>
                            @if ($product->isLowStock())
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Low stock
                                </span>
                            @endif
                            @if($product->is_available)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Available
                                </span>
                            @else
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Unavailable
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($product->status ?? 'active') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('products.show', $product) }}" 
                                   class="text-blue-600 hover:text-blue-900 transition-colors"
                                   title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('products.edit', $product) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                   title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-900 transition-colors"
                                            title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No products found. <a href="{{ route('products.create', ['hub' => $hub ?? 'kidz_mart']) }}" class="text-blue-600 hover:text-blue-900">Create your first product</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
