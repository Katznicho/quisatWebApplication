@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Support Child</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Update child information and photos.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('support-children.update', $child) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="child_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Child Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="child_name"
                           id="child_name"
                           value="{{ old('child_name', $child->child_name) }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('child_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                        Age
                    </label>
                    <input type="number"
                           name="age"
                           id="age"
                           min="0"
                           max="25"
                           value="{{ old('age', $child->age) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('age')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monthly_fee" class="block text-sm font-medium text-gray-700 mb-2">
                        Monthly Fee
                    </label>
                    <input type="number"
                           name="monthly_fee"
                           id="monthly_fee"
                           step="0.01"
                           min="0"
                           value="{{ old('monthly_fee', $child->monthly_fee) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('monthly_fee')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                        Currency
                    </label>
                    <input type="text"
                           name="currency"
                           id="currency"
                           value="{{ old('currency', $child->currency ?? (auth()->user()->business->currency_code ?? 'UGX')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="story" class="block text-sm font-medium text-gray-700 mb-2">
                        Story
                    </label>
                    <textarea name="story"
                              id="story"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('story', $child->story) }}</textarea>
                    @error('story')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Organisation Contact</h2>
                </div>

                <div>
                    <label for="organisation_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Organisation Name
                    </label>
                    <input type="text"
                           name="organisation_name"
                           id="organisation_name"
                           value="{{ old('organisation_name', $child->organisation_name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('organisation_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="organisation_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Organisation Email
                    </label>
                    <input type="email"
                           name="organisation_email"
                           id="organisation_email"
                           value="{{ old('organisation_email', $child->organisation_email) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('organisation_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="organisation_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Organisation Phone
                    </label>
                    <input type="text"
                           name="organisation_phone"
                           id="organisation_phone"
                           value="{{ old('organisation_phone', $child->organisation_phone) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('organisation_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="organisation_website" class="block text-sm font-medium text-gray-700 mb-2">
                        Organisation Website
                    </label>
                    <input type="text"
                           name="organisation_website"
                           id="organisation_website"
                           value="{{ old('organisation_website', $child->organisation_website) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('organisation_website')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select name="status"
                            id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" {{ old('status', $child->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $child->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="is_featured" class="block text-sm font-medium text-gray-700 mb-2">
                        Featured
                    </label>
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="is_featured"
                               id="is_featured"
                               value="1"
                               {{ old('is_featured', $child->is_featured) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_featured" class="ml-2 block text-sm text-gray-700">
                            Highlight this child as featured
                        </label>
                    </div>
                </div>

                @if($child->images->count() > 0)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Photos
                    </label>
                    <div class="flex flex-wrap gap-4">
                        @foreach($child->images as $image)
                            <div class="relative">
                                <img src="{{ Storage::url($image->image_url) }}"
                                     alt="Child photo"
                                     class="h-24 w-24 object-cover rounded border">
                                @if($image->is_primary)
                                    <span class="absolute -top-2 -right-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">
                                        Main
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="md:col-span-2">
                    <label for="photos" class="block text-sm font-medium text-gray-700 mb-2">
                        Add More Photos
                    </label>
                    <input type="file"
                           name="photos[]"
                           id="photos"
                           accept="image/*"
                           multiple
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">
                        You can upload additional photos. Max file size: 2MB each.
                    </p>
                    @error('photos.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('support-children.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Update Child
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

