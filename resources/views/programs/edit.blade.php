<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Edit Program') }}
                </h2>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <form action="{{ route('programs.update', $program) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="space-y-8">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h2>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Program Name *
                                    </label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $program->name) }}" 
                                           placeholder="Enter program name"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('name') border-red-500 @enderror" required>
                                    @error('name')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <textarea id="description" name="description" rows="4" 
                                              placeholder="Enter program description"
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('description') border-red-500 @enderror">{{ old('description', $program->description) }}</textarea>
                                    @error('description')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="age-group" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Age Group
                                        </label>
                                        <input type="text" id="age-group" name="age-group" value="{{ old('age-group', $program->{'age-group'}) }}" 
                                               placeholder="e.g., 5-12 years"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('age-group') border-red-500 @enderror">
                                        @error('age-group')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Status *
                                        </label>
                                        <select id="status" name="status" 
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('status') border-red-500 @enderror" required>
                                            <option value="active" {{ old('status', $program->status) === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $program->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Upload -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Media</h2>
                            
                            <div class="space-y-6">
                                @if($program->image || $program->video)
                                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Media:</p>
                                        @if($program->image)
                                            <div class="flex items-center space-x-4">
                                                <img src="{{ asset('storage/' . $program->image) }}" alt="Current image" class="h-20 w-20 object-cover rounded">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Image uploaded</span>
                                            </div>
                                        @endif
                                        @if($program->video)
                                            <div class="flex items-center space-x-4 mt-2">
                                                <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Video uploaded</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div>
                                    <label for="media_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Media Type
                                    </label>
                                    <select id="media_type" name="media_type" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('media_type') border-red-500 @enderror">
                                        <option value="">Keep current media</option>
                                        <option value="image" {{ old('media_type') === 'image' ? 'selected' : '' }}>Replace with Image</option>
                                        <option value="video" {{ old('media_type') === 'video' ? 'selected' : '' }}>Replace with Video</option>
                                        <option value="remove">Remove media</option>
                                    </select>
                                    @error('media_type')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div id="image-upload-section" class="hidden">
                                    <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Program Image
                                    </label>
                                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('image') border-red-500 @enderror">
                                    @error('image')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Supported formats: JPEG, PNG, GIF, WebP (Max: 2MB)
                                    </p>
                                </div>

                                <div id="video-upload-section" class="hidden">
                                    <label for="video" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Program Video
                                    </label>
                                    <input type="file" id="video" name="video" accept="video/mp4,video/mov,video/avi,video/quicktime"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('video') border-red-500 @enderror">
                                    @error('video')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Supported formats: MP4, MOV, AVI, QuickTime (Max: 10MB)
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                                Update Program
                            </button>
                            <a href="{{ route('programs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaTypeSelect = document.getElementById('media_type');
    const imageUploadSection = document.getElementById('image-upload-section');
    const videoUploadSection = document.getElementById('video-upload-section');
    const imageInput = document.getElementById('image');
    const videoInput = document.getElementById('video');

    function toggleMediaUpload() {
        const mediaType = mediaTypeSelect.value;
        
        if (mediaType === 'image') {
            imageUploadSection.classList.remove('hidden');
            videoUploadSection.classList.add('hidden');
            videoInput.value = ''; // Clear video input
        } else if (mediaType === 'video') {
            imageUploadSection.classList.add('hidden');
            videoUploadSection.classList.remove('hidden');
            imageInput.value = ''; // Clear image input
        } else {
            imageUploadSection.classList.add('hidden');
            videoUploadSection.classList.add('hidden');
            imageInput.value = '';
            videoInput.value = '';
        }
    }

    // Event listener
    mediaTypeSelect.addEventListener('change', toggleMediaUpload);

    // Initialize on page load
    toggleMediaUpload();
});
</script>

