<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Create Advertisement') }}
                </h2>
            </div>

            <!-- Header Section -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create Advertisement</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Create a new advertisement for your business</p>
            </div>

            @if (session('success'))
                <div class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('advertisements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                
                <!-- Basic Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Basic Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Advertisement Title *
                            </label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" required
                                   placeholder="e.g., Summer Sale - 50% Off All Items"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Category
                            </label>
                            <input type="text" id="category" name="category" value="{{ old('category') }}"
                                   placeholder="e.g., Sales, Events, Announcements"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('category')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description *
                        </label>
                        <textarea id="description" name="description" rows="4" required
                                  placeholder="Describe your advertisement in detail. What are you promoting? What should users know about this offer?"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Media Upload -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Media</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="media_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Media Type *
                            </label>
                            <select id="media_type" name="media_type" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select media type</option>
                                <option value="image" {{ old('media_type') === 'image' ? 'selected' : '' }}>Image</option>
                                <option value="video" {{ old('media_type') === 'video' ? 'selected' : '' }}>Video</option>
                                <option value="text" {{ old('media_type') === 'text' ? 'selected' : '' }}>Text Only</option>
                            </select>
                            @error('media_type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="media-upload-section" class="hidden">
                            <label for="media" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Upload Media
                            </label>
                            <input type="file" id="media" name="media" accept="image/*,video/*"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('media')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Supported formats: JPG, PNG, GIF, SVG, MP4, AVI, MOV (Max: 10MB)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Target Audience -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Target Audience</h2>
                    
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Select target audience *
                        </label>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="target_audience[]" value="all_users" 
                                       {{ in_array('all_users', old('target_audience', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">All Users</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="target_audience[]" value="staff" 
                                       {{ in_array('staff', old('target_audience', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Staff</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="target_audience[]" value="students" 
                                       {{ in_array('students', old('target_audience', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Students</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="target_audience[]" value="parents" 
                                       {{ in_array('parents', old('target_audience', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Parents</span>
                            </label>
                        </div>
                        @error('target_audience')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Scheduling</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Start Date *
                            </label>
                            <input type="datetime-local" id="start_date" name="start_date" value="{{ old('start_date') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                End Date *
                            </label>
                            <input type="datetime-local" id="end_date" name="end_date" value="{{ old('end_date') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Budget -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Budget</h2>
                    
                    <div>
                        <label for="budget" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Budget (Optional)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                            </div>
                            <input type="number" id="budget" name="budget" value="{{ old('budget') }}" step="0.01" min="0"
                                   placeholder="0.00"
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        @error('budget')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Status</h2>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status *
                        </label>
                        <select id="status" name="status" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Draft: Advertisement will not be visible to users. Published: Advertisement will be visible to users.
                        </p>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('advertisements.index') }}" 
                       class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                        Create Advertisement
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaTypeSelect = document.getElementById('media_type');
    const mediaUploadSection = document.getElementById('media-upload-section');

    // Show/hide media upload based on media type
    function toggleMediaUpload() {
        const mediaType = mediaTypeSelect.value;
        if (mediaType === 'image' || mediaType === 'video') {
            mediaUploadSection.classList.remove('hidden');
        } else {
            mediaUploadSection.classList.add('hidden');
        }
    }

    // Set minimum date to today
    const today = new Date().toISOString().slice(0, 16);
    document.getElementById('start_date').min = today;
    document.getElementById('end_date').min = today;

    // Update end date minimum when start date changes
    document.getElementById('start_date').addEventListener('change', function() {
        const startDate = this.value;
        document.getElementById('end_date').min = startDate;
    });

    // Event listeners
    mediaTypeSelect.addEventListener('change', toggleMediaUpload);

    // Initialize on page load
    toggleMediaUpload();
});
</script>
