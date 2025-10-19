<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $kidsEvent->title }}</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Event Details & Analytics</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('kids-events.edit', $kidsEvent) }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </a>
                            <a href="{{ route('kids-events.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                <span>Back</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <!-- Event Details -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Basic Information -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</label>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $kidsEvent->title }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kidsEvent->status_badge_color }}">
                                            {{ ucfirst($kidsEvent->status) }}
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->category }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Price</label>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $kidsEvent->formatted_price }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Host Organization</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->host_organization }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->location ?? 'Not specified' }}</p>
                                    </div>
                                </div>
                                
                                @if($kidsEvent->description)
                                <div class="mt-6">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                                    <p class="text-gray-900 dark:text-white mt-2">{{ $kidsEvent->description }}</p>
                                </div>
                                @endif
                            </div>

                            <!-- Event Image -->
                            @if($kidsEvent->image_url)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Image</h2>
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $kidsEvent->image_url) }}" alt="{{ $kidsEvent->title }}" 
                                         class="max-w-full h-auto rounded-lg shadow-sm">
                                </div>
                            </div>
                            @endif

                            <!-- Schedule Information -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Schedule</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date & Time</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->start_date->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date & Time</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->end_date->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            @if($kidsEvent->contact_email || $kidsEvent->contact_phone || $kidsEvent->contact_info)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @if($kidsEvent->contact_email)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                                        <a href="mailto:{{ $kidsEvent->contact_email }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $kidsEvent->contact_email }}
                                        </a>
                                    </div>
                                    @endif
                                    
                                    @if($kidsEvent->contact_phone)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</label>
                                        <a href="tel:{{ $kidsEvent->contact_phone }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $kidsEvent->contact_phone }}
                                        </a>
                                    </div>
                                    @endif
                                    
                                    @if($kidsEvent->contact_info)
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Additional Info</label>
                                        <p class="text-gray-900 dark:text-white mt-1">{{ $kidsEvent->contact_info }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Analytics Sidebar -->
                        <div class="space-y-6">
                            
                            <!-- Participant Metrics -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Participants</h2>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Current Participants</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $kidsEvent->current_participants }}</span>
                                    </div>
                                    
                                    @if($kidsEvent->max_participants)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Maximum Participants</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $kidsEvent->max_participants }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Available Spots</span>
                                        <span class="text-lg font-semibold {{ $kidsEvent->spots_available > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $kidsEvent->spots_available }}
                                        </span>
                                    </div>
                                    @else
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Capacity</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Unlimited</span>
                                    </div>
                                    @endif
                                    
                                    @if($kidsEvent->is_full)
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">
                                        <strong>Event is Full!</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Event Settings -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Settings</h2>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Parent Permission Required</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kidsEvent->requires_parent_permission ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $kidsEvent->requires_parent_permission ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Featured Event</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kidsEvent->is_featured ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $kidsEvent->is_featured ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">External Event</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kidsEvent->is_external ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $kidsEvent->is_external ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Info -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Info</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->updated_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsEvent->creator->name ?? 'Unknown' }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Event ID</label>
                                        <p class="text-gray-900 dark:text-white font-mono text-sm">#{{ $kidsEvent->id }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                                
                                <div class="space-y-3">
                                    <a href="{{ route('kids-events.edit', $kidsEvent) }}" 
                                       class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center block">
                                        Edit Event
                                    </a>
                                    
                                    <button onclick="toggleFeatured({{ $kidsEvent->id }})" 
                                            class="w-full {{ $kidsEvent->is_featured ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg">
                                        {{ $kidsEvent->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}
                                    </button>
                                    
                                    <form method="POST" action="{{ route('kids-events.destroy', $kidsEvent) }}" class="inline w-full" 
                                          onsubmit="return confirm('Are you sure you want to delete this event?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                                            Delete Event
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for quick actions -->
    <script>
        function toggleFeatured(id) {
            if (confirm('Are you sure you want to change the featured status of this event?')) {
                fetch(`/kids-events/${id}/toggle-featured`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the event.');
                });
            }
        }
    </script>
</x-app-layout>








