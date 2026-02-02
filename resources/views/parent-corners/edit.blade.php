<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Parent Corner Event</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Update event details and settings</p>
                        </div>
                        <a href="{{ route('parent-corners.show', $parentCorner) }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Back to View</span>
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('parent-corners.update', $parentCorner) }}" enctype="multipart/form-data" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-8">
                        
                        <!-- Basic Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Event Title *</label>
                                    <input type="text" id="title" name="title" value="{{ old('title', $parentCorner->title) }}" 
                                           placeholder="Summer Camp, Science Fair, etc."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('title') border-red-500 @enderror" required>
                                    @error('title')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                    <textarea id="description" name="description" rows="4" 
                                              placeholder="Full event description for parents."
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('description') border-red-500 @enderror">{{ old('description', $parentCorner->description) }}</textarea>
                                    @error('description')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Host Organization *</label>
                                    <input type="text" id="category" name="category" value="{{ old('category', $parentCorner->category) }}" 
                                           placeholder="Tech Kids Academy, Green Valley Parks, etc."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('category') border-red-500 @enderror" required>
                                    @error('category')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category *</label>
                                    <input type="text" id="category" name="category" value="{{ old('category', $parentCorner->category) }}" 
                                           placeholder="e.g., Workshop, Seminar, Support Group, Training, etc."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('category') border-red-500 @enderror" 
                                           required>
                                    @error('category')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location</label>
                                    <input type="text" id="location" name="location" value="{{ old('location', $parentCorner->location) }}" 
                                           placeholder="Event location"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('location') border-red-500 @enderror">
                                    @error('location')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price</label>
                                    <input type="number" id="price" name="price" value="{{ old('price', $parentCorner->price) }}" 
                                           step="0.01" min="0" placeholder="0.00 for free"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('price') border-red-500 @enderror">
                                    @error('price')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Event Schedule -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Schedule</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date/Time *</label>
                                    <input type="datetime-local" id="start_date" name="start_date" 
                                           value="{{ old('start_date', $parentCorner->start_date ? $parentCorner->start_date->format('Y-m-d\TH:i') : '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('start_date') border-red-500 @enderror" required>
                                    @error('start_date')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date/Time *</label>
                                    <input type="datetime-local" id="end_date" name="end_date" 
                                           value="{{ old('end_date', $parentCorner->end_date ? $parentCorner->end_date->format('Y-m-d\TH:i') : '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('end_date') border-red-500 @enderror" required>
                                    @error('end_date')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                                    <select id="status" name="status" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('status') border-red-500 @enderror" required>
                                        <option value="draft" {{ old('status', $parentCorner->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="published" {{ old('status', $parentCorner->status) === 'published' ? 'selected' : '' }}>Published</option>
                                    </select>
                                    @error('status')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Event Image -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Image</h2>
                            
                            @if($parentCorner->image_url)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Image</label>
                                <img src="{{ asset('storage/' . $parentCorner->image_url) }}" alt="{{ $parentCorner->title }}" 
                                     class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            @endif
                            
                            <div>
                                <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Update Image</label>
                                <input type="file" id="image" name="image" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('image') border-red-500 @enderror">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose file (JPG, PNG, GIF - Max 2MB)</p>
                                @error('image')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Participant Settings -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Participant Settings</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="max_participants" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maximum Participants</label>
                                    <input type="number" id="max_participants" name="max_participants" value="{{ old('max_participants', $parentCorner->max_participants) }}" 
                                           min="1" placeholder="Leave empty for unlimited"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('max_participants') border-red-500 @enderror">
                                    @error('max_participants')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="current_participants" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Participants</label>
                                    <input type="number" id="current_participants" name="current_participants" value="{{ old('current_participants', $parentCorner->current_participants) }}" 
                                           min="0" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('current_participants') border-red-500 @enderror">
                                    @error('current_participants')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="flex items-center">
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="is_featured" name="is_featured" value="1" 
                                           {{ old('is_featured', $parentCorner->is_featured) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured event</label>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact Email</label>
                                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $parentCorner->contact_email) }}" 
                                           placeholder="contact@organization.com"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('contact_email') border-red-500 @enderror">
                                    @error('contact_email')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact Phone</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $parentCorner->contact_phone) }}" 
                                           placeholder="+1 (555) 123-4567"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('contact_phone') border-red-500 @enderror">
                                    @error('contact_phone')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="contact_info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Additional Contact Info</label>
                                    <textarea id="contact_info" name="contact_info" rows="3" 
                                              placeholder="Additional contact information or special instructions"
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('contact_info') border-red-500 @enderror">{{ old('contact_info', $parentCorner->contact_info) }}</textarea>
                                    @error('contact_info')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Organizer Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Organizer Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="organizer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Organizer Name</label>
                                    <input type="text" id="organizer_name" name="organizer_name" value="{{ old('organizer_name', $parentCorner->organizer_name) }}" 
                                           placeholder="Organizer or contact person name"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('organizer_name') border-red-500 @enderror">
                                    @error('organizer_name')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="organizer_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Organizer Email</label>
                                    <input type="email" id="organizer_email" name="organizer_email" value="{{ old('organizer_email', $parentCorner->organizer_email) }}" 
                                           placeholder="organizer@organization.com"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('organizer_email') border-red-500 @enderror">
                                    @error('organizer_email')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="organizer_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Organizer Phone</label>
                                    <input type="tel" id="organizer_phone" name="organizer_phone" value="{{ old('organizer_phone', $parentCorner->organizer_phone) }}" 
                                           placeholder="+1 (555) 123-4567"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('organizer_phone') border-red-500 @enderror">
                                    @error('organizer_phone')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="organizer_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Organizer Address</label>
                                    <input type="text" id="organizer_address" name="organizer_address" value="{{ old('organizer_address', $parentCorner->organizer_address) }}" 
                                           placeholder="Organizer address"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('organizer_address') border-red-500 @enderror">
                                    @error('organizer_address')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('parent-corners.show', $parentCorner) }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                Update Event
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>










