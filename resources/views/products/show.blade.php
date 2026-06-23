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
            <a href="{{ route('products.index', ['hub' => $hub ?? 'kidz_mart']) }}"
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

                    @if($product->sku)
                    <div>
                        <label class="text-sm font-medium text-gray-500">SKU / Item Code</label>
                        <p class="text-gray-900 font-mono">{{ $product->sku }}</p>
                    </div>
                    @endif

                    @if($product->description)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="text-gray-900">{{ $product->description }}</p>
                    </div>
                    @endif

                    @if($product->key_features)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Key Features</label>
                        <div class="text-gray-900 whitespace-pre-line">{{ $product->key_features }}</div>
                    </div>
                    @endif

                    @if($product->whats_in_box)
                    <div>
                        <label class="text-sm font-medium text-gray-500">What's in the Box</label>
                        <div class="text-gray-900 whitespace-pre-line">{{ $product->whats_in_box }}</div>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-500">Price</label>
                        @if($product->isPromotionActive())
                            <p class="text-sm text-gray-500 line-through">{{ $product->business->currency_code ?? 'UGX' }} {{ number_format($product->price, 0) }}</p>
                            <p class="text-2xl font-bold text-red-600">{{ $product->business->currency_code ?? 'UGX' }} {{ number_format($product->sale_price, 0) }}</p>
                            @if($product->discountPercent())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                                    {{ $product->discountPercent() }}% OFF
                                </span>
                            @endif
                            @if($product->promotion_label)
                                <p class="text-sm text-amber-700 mt-1">{{ $product->promotion_label }}</p>
                            @endif
                        @else
                            <p class="text-2xl font-bold text-blue-600">{{ $product->business->currency_code ?? 'UGX' }} {{ number_format($product->price, 0) }}</p>
                        @endif
                    </div>

                    @if($product->category)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Category</label>
                        <p class="text-gray-900">{{ $product->category }}</p>
                    </div>
                    @endif

                    @if($product->grade)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Grade</label>
                        <p class="text-gray-900">{{ $product->grade }}</p>
                    </div>
                    @endif

                    @if($product->isStationery())
                    <div>
                        <label class="text-sm font-medium text-gray-500">Delivery</label>
                        <p class="text-gray-900">{{ (int) ($product->delivery_days ?? 3) }} day(s)</p>
                    </div>
                    @if($product->quality_grade)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Quality</label>
                        <p class="text-gray-900">{{ \App\Support\StationeryHub::qualityOptions()[$product->quality_grade] ?? ucfirst($product->quality_grade) }}</p>
                    </div>
                    @endif
                    @endif

                    @if($product->is_on_sale)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Promotion</label>
                        <p class="text-gray-900">
                            @if($product->isPromotionActive())
                                <span class="text-green-700 font-medium">Active</span>
                            @else
                                <span class="text-gray-500">Scheduled or ended</span>
                            @endif
                            @if($product->promotion_starts_at || $product->promotion_ends_at)
                                <span class="block text-sm text-gray-500 mt-1">
                                    @if($product->promotion_starts_at) From {{ $product->promotion_starts_at->format('M d, Y H:i') }} @endif
                                    @if($product->promotion_ends_at) until {{ $product->promotion_ends_at->format('M d, Y H:i') }} @endif
                                </span>
                            @endif
                        </p>
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
