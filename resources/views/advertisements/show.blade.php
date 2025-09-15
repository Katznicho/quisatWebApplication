<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $advertisement->title }}</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Advertisement Details & Analytics</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('advertisements.edit', $advertisement) }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </a>
                            <a href="{{ route('advertisements.index') }}" 
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
                        
                        <!-- Advertisement Details -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Basic Information -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Advertisement Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</label>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $advertisement->title }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($advertisement->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($advertisement->status === 'scheduled') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                            {{ ucfirst($advertisement->status) }}
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</label>
                                        <p class="text-gray-900 dark:text-white">{{ ucfirst($advertisement->type ?? 'N/A') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Priority</label>
                                        <p class="text-gray-900 dark:text-white">{{ ucfirst($advertisement->priority ?? 'Normal') }}</p>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                                    <p class="text-gray-900 dark:text-white mt-2">
                                        @if(is_array($advertisement->description))
                                            {{ implode(', ', $advertisement->description) }}
                                        @else
                                            {{ $advertisement->description }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Media Content -->
                            @if($advertisement->media_url || $advertisement->video_url)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Media Content</h2>
                                
                                @if($advertisement->media_url)
                                <div class="mb-4">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Image</label>
                                    <div class="mt-2">
                                        <img src="{{ $advertisement->media_url }}" alt="{{ $advertisement->title }}" 
                                             class="max-w-full h-auto rounded-lg shadow-sm">
                                    </div>
                                </div>
                                @endif
                                
                                @if($advertisement->video_url)
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Video</label>
                                    <div class="mt-2">
                                        <video controls class="max-w-full h-auto rounded-lg shadow-sm">
                                            <source src="{{ $advertisement->video_url }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Call to Action -->
                            @if($advertisement->cta_text || $advertisement->cta_url)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Call to Action</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @if($advertisement->cta_text)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">CTA Text</label>
                                        <p class="text-gray-900 dark:text-white">
                                            @if(is_array($advertisement->cta_text))
                                                {{ implode(', ', $advertisement->cta_text) }}
                                            @else
                                                {{ $advertisement->cta_text }}
                                            @endif
                                        </p>
                                    </div>
                                    @endif
                                    
                                    @if($advertisement->cta_url)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">CTA URL</label>
                                        <a href="{{ $advertisement->cta_url }}" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 break-all">
                                            {{ $advertisement->cta_url }}
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Targeting & Schedule -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Targeting & Schedule</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</label>
                                        <p class="text-gray-900 dark:text-white">{{ $advertisement->start_date ? \Carbon\Carbon::parse($advertisement->start_date)->format('M d, Y') : 'N/A' }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</label>
                                        <p class="text-gray-900 dark:text-white">{{ $advertisement->end_date ? \Carbon\Carbon::parse($advertisement->end_date)->format('M d, Y') : 'N/A' }}</p>
                                    </div>
                                    
                                    @if($advertisement->target_audience)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Target Audience</label>
                                        <p class="text-gray-900 dark:text-white">
                                            @if(is_array($advertisement->target_audience))
                                                {{ implode(', ', $advertisement->target_audience) }}
                                            @else
                                                {{ $advertisement->target_audience }}
                                            @endif
                                        </p>
                                    </div>
                                    @endif
                                    
                                    @if($advertisement->budget)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Budget</label>
                                        <p class="text-gray-900 dark:text-white">{{ number_format($advertisement->budget, 2) }} {{ $advertisement->currency ?? 'USD' }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Analytics Sidebar -->
                        <div class="space-y-6">
                            
                            <!-- Performance Metrics -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Performance</h2>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Views</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $advertisement->views ?? 0 }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Clicks</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $advertisement->clicks ?? 0 }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Conversions</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $advertisement->conversions ?? 0 }}</span>
                                    </div>
                                    
                                    @if($advertisement->views > 0)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">CTR</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format(($advertisement->clicks / $advertisement->views) * 100, 2) }}%</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Advertisement Info -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Advertisement Info</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</label>
                                        <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($advertisement->created_at)->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                                        <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($advertisement->updated_at)->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Advertisement ID</label>
                                        <p class="text-gray-900 dark:text-white font-mono text-sm">#{{ $advertisement->id }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                                
                                <div class="space-y-3">
                                    <a href="{{ route('advertisements.edit', $advertisement) }}" 
                                       class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center block">
                                        Edit Advertisement
                                    </a>
                                    
                                    <button onclick="duplicateAdvertisement({{ $advertisement->id }})" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                        Duplicate
                                    </button>
                                    
                                    <button onclick="toggleStatus({{ $advertisement->id }})" 
                                            class="w-full {{ $advertisement->status === 'active' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg">
                                        {{ $advertisement->status === 'active' ? 'Pause' : 'Activate' }}
                                    </button>
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
        function duplicateAdvertisement(id) {
            if (confirm('Are you sure you want to duplicate this advertisement?')) {
                // Implementation for duplicating advertisement
                alert('Duplicate functionality will be implemented');
            }
        }

        function toggleStatus(id) {
            if (confirm('Are you sure you want to change the status of this advertisement?')) {
                // Implementation for toggling status
                alert('Status toggle functionality will be implemented');
            }
        }
    </script>
</x-app-layout>