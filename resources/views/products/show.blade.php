@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Product Details</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('products.edit', $product) }}" 
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('products.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <!-- Product Images -->
            <div>
                @if($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}" 
                         alt="{{ $product->name }}" 
                         class="w-full h-64 object-cover rounded-lg mb-4">
                @endif

                @if($product->images->count() > 0)
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($product->images as $image)
                            <img src="{{ Storage::url($image->image_url) }}" 
                                 alt="Product image" 
                                 class="w-full h-24 object-cover rounded border">
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Details -->
            <div>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="text-lg font-semibold text-gray-900">{{ $product->name }}</p>
                    </div>

                    @if($product->description)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="text-gray-900">{{ $product->description }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-500">Price</label>
                        <p class="text-2xl font-bold text-blue-600">UGX {{ number_format($product->price, 0) }}</p>
                    </div>

                    @if($product->category)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Category</label>
                        <p class="text-gray-900">{{ $product->category }}</p>
                    </div>
                    @endif

                    @if(is_array($product->sizes) && count($product->sizes) > 0)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Sizes</label>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($product->sizes as $size)
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ $size }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-500">Stock Quantity</label>
                        <p class="text-gray-900">{{ $product->stock_quantity }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Availability</label>
                        <p>
                            @if($product->is_available)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Available
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Unavailable
                                </span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($product->status ?? 'active') }}
                            </span>
                        </p>
                    </div>

                    @if($product->business)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Business</label>
                        <p class="text-gray-900">{{ $product->business->name }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-gray-900">{{ $product->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
