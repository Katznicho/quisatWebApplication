<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create Kids Fun Venue</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Add a new fun venue for kids</p>
                        </div>
                        <a href="{{ route('kids-fun-venues.index') }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Back</span>
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('kids-fun-venues.store') }}" enctype="multipart/form-data" class="p-6">
                    @csrf
                    
                    <div class="space-y-8">
                        
                        <!-- Basic Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Venue Name *</label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}" 
                                           placeholder="Fun Park, Playground, etc."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('name') border-red-500 @enderror" required>
                                    @error('name')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                    <textarea id="description" name="description" rows="4" 
                                              placeholder="Describe the venue and what makes it special."
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                                    @error('description')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location *</label>
                                    <input type="text" id="location" name="location" value="{{ old('location') }}" 
                                           placeholder="Full address or location"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('location') border-red-500 @enderror" required>
                                    @error('location')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Operating Hours -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Operating Hours</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="open_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Open Time *</label>
                                    <input type="time" id="open_time" name="open_time" value="{{ old('open_time') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('open_time') border-red-500 @enderror" required>
                                    @error('open_time')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="close_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Close Time *</label>
                                    <input type="time" id="close_time" name="close_time" value="{{ old('close_time') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('close_time') border-red-500 @enderror" required>
                                    @error('close_time')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Activities -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Activities / Services Offered</h2>
                            
                            <div id="activities-container">
                                <div class="activity-item mb-2 flex gap-2">
                                    <input type="text" name="activities[]" placeholder="e.g., Swimming, Playground, Trampoline" 
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                                    <button type="button" onclick="removeActivity(this)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">Remove</button>
                                </div>
                            </div>
                            <button type="button" onclick="addActivity()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Add Activity</button>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">List all activities or services offered at this venue</p>
                        </div>

                        <!-- Prices -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Pricing</h2>
                            
                            <div id="prices-container">
                                <div class="price-item mb-2 flex gap-2">
                                    <input type="text" name="prices[]" placeholder="e.g., Entry: UGX 10,000, Children: UGX 5,000" 
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                                    <button type="button" onclick="removePrice(this)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">Remove</button>
                                </div>
                            </div>
                            <button type="button" onclick="addPrice()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Add Price</button>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Enter pricing information for different services or age groups</p>
                        </div>

                        <!-- Images -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Images</h2>
                            
                            <div>
                                <label for="images" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload Images</label>
                                <input type="file" id="images" name="images[]" accept="image/*" multiple
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('images.*') border-red-500 @enderror">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">You can select multiple images (JPG, PNG, GIF - Max 2MB each)</p>
                                @error('images.*')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Website & Social Media -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Website & Social Media</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="website_link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Website Link</label>
                                    <input type="url" id="website_link" name="website_link" value="{{ old('website_link') }}" 
                                           placeholder="https://www.example.com"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('website_link') border-red-500 @enderror">
                                    @error('website_link')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="facebook" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Facebook URL</label>
                                    <input type="url" id="facebook" name="facebook" value="{{ old('facebook') }}" 
                                           placeholder="https://facebook.com/..."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                                </div>
                                
                                <div>
                                    <label for="instagram" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Instagram URL</label>
                                    <input type="url" id="instagram" name="instagram" value="{{ old('instagram') }}" 
                                           placeholder="https://instagram.com/..."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                                </div>
                                
                                <div>
                                    <label for="twitter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Twitter/X URL</label>
                                    <input type="url" id="twitter" name="twitter" value="{{ old('twitter') }}" 
                                           placeholder="https://twitter.com/..."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                                </div>
                                
                                <div>
                                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">WhatsApp</label>
                                    <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" 
                                           placeholder="Phone number or WhatsApp link"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="booking_link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Booking Link</label>
                                    <input type="url" id="booking_link" name="booking_link" value="{{ old('booking_link') }}" 
                                           placeholder="https://booking.example.com"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('booking_link') border-red-500 @enderror">
                                    @error('booking_link')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Status</h2>
                            
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                                <select id="status" name="status" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('status') border-red-500 @enderror" required>
                                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Draft venues are not visible to users. Publish when ready.</p>
                                @error('status')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('kids-fun-venues.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                Create Venue
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function addActivity() {
    const container = document.getElementById('activities-container');
    const div = document.createElement('div');
    div.className = 'activity-item mb-2 flex gap-2';
    div.innerHTML = `
        <input type="text" name="activities[]" placeholder="e.g., Swimming, Playground, Trampoline" 
               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
        <button type="button" onclick="removeActivity(this)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">Remove</button>
    `;
    container.appendChild(div);
}

function removeActivity(button) {
    button.parentElement.remove();
}

function addPrice() {
    const container = document.getElementById('prices-container');
    const div = document.createElement('div');
    div.className = 'price-item mb-2 flex gap-2';
    div.innerHTML = `
        <input type="text" name="prices[]" placeholder="e.g., Entry: UGX 10,000, Children: UGX 5,000" 
               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
        <button type="button" onclick="removePrice(this)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">Remove</button>
    `;
    container.appendChild(div);
}

function removePrice(button) {
    button.parentElement.remove();
}
</script>

