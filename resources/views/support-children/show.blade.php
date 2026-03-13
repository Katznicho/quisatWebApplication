@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $child->child_name }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Support Child Details</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('support-children.edit', $child) }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('support-children.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <div>
                @php
                    $primaryImage = $child->images->firstWhere('is_primary', true) ?? $child->images->first();
                @endphp

                @if($primaryImage)
                    <img src="{{ Storage::url($primaryImage->image_url) }}"
                         alt="{{ $child->child_name }}"
                         class="w-full h-64 object-cover rounded-lg mb-4">
                @endif

                @if($child->images->count() > 1)
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($child->images as $image)
                            @if(!$image->is_primary)
                                <img src="{{ Storage::url($image->image_url) }}"
                                     alt="Child photo"
                                     class="w-full h-24 object-cover rounded border">
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="text-lg font-semibold text-gray-900">{{ $child->child_name }}</p>
                    </div>

                    @if(!is_null($child->age))
                    <div>
                        <label class="text-sm font-medium text-gray-500">Age</label>
                        <p class="text-gray-900">{{ $child->age }} years</p>
                    </div>
                    @endif

                    @if(!is_null($child->monthly_fee))
                    <div>
                        <label class="text-sm font-medium text-gray-500">Monthly Fee</label>
                        <p class="text-2xl font-bold text-blue-600">
                            {{ $child->currency ?? 'UGX' }} {{ number_format($child->monthly_fee, 0) }}
                        </p>
                    </div>
                    @endif

                    @if($child->story)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Story</label>
                        <p class="text-gray-900 whitespace-pre-line">{{ $child->story }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $child->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($child->status ?? 'active') }}
                            </span>
                            @if($child->is_featured)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Featured
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($child->business)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Business</label>
                        <p class="text-gray-900">{{ $child->business->name }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-gray-900">{{ $child->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Organisation Contact</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($child->organisation_name)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Organisation</label>
                        <p class="text-gray-900">{{ $child->organisation_name }}</p>
                    </div>
                @endif

                @if($child->organisation_email)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900">{{ $child->organisation_email }}</p>
                    </div>
                @endif

                @if($child->organisation_phone)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="text-gray-900">{{ $child->organisation_phone }}</p>
                    </div>
                @endif

                @if($child->organisation_website)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Website</label>
                        <p class="text-blue-600">
                            <a href="{{ $child->organisation_website }}" target="_blank" rel="noopener noreferrer">
                                {{ $child->organisation_website }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

