<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $kidsFunVenue->name }}</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Kids Fun Venue Details</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('kids-fun-venues.edit', $kidsFunVenue) }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </a>
                            <a href="{{ route('kids-fun-venues.index') }}" 
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
                        
                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Images -->
                            @if($kidsFunVenue->images && count($kidsFunVenue->images) > 0)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Images</h2>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    @foreach($kidsFunVenue->images as $image)
                                        <div>
                                            <img src="{{ Storage::url($image) }}" alt="{{ $kidsFunVenue->name }}" 
                                                 class="w-full h-48 object-cover rounded-lg shadow-sm">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Basic Information -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Venue Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</label>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $kidsFunVenue->name }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $kidsFunVenue->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                            {{ ucfirst($kidsFunVenue->status) }}
                                        </span>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsFunVenue->location }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Open Time</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsFunVenue->open_time }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Close Time</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsFunVenue->close_time }}</p>
                                    </div>
                                </div>
                                
                                @if($kidsFunVenue->description)
                                <div class="mt-6">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                                    <p class="text-gray-900 dark:text-white mt-2">{{ $kidsFunVenue->description }}</p>
                                </div>
                                @endif
                            </div>

                            <!-- Activities -->
                            @if($kidsFunVenue->activities && count($kidsFunVenue->activities) > 0)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Activities / Services Offered</h2>
                                <ul class="list-disc list-inside space-y-2">
                                    @foreach($kidsFunVenue->activities as $activity)
                                        <li class="text-gray-900 dark:text-white">{{ $activity }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <!-- Prices -->
                            @if($kidsFunVenue->prices && count($kidsFunVenue->prices) > 0)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Pricing</h2>
                                <ul class="list-disc list-inside space-y-2">
                                    @foreach($kidsFunVenue->prices as $price)
                                        <li class="text-gray-900 dark:text-white">{{ $price }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <!-- Links -->
                            @if($kidsFunVenue->website_link || $kidsFunVenue->booking_link || ($kidsFunVenue->social_media_handles && count($kidsFunVenue->social_media_handles) > 0))
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Links</h2>
                                
                                <div class="space-y-3">
                                    @if($kidsFunVenue->website_link)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Website</label>
                                        <p class="mt-1">
                                            <a href="{{ $kidsFunVenue->website_link }}" target="_blank" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 break-all">
                                                {{ $kidsFunVenue->website_link }}
                                            </a>
                                        </p>
                                    </div>
                                    @endif
                                    
                                    @if($kidsFunVenue->booking_link)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Booking</label>
                                        <p class="mt-1">
                                            <a href="{{ $kidsFunVenue->booking_link }}" target="_blank" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 break-all">
                                                {{ $kidsFunVenue->booking_link }}
                                            </a>
                                        </p>
                                    </div>
                                    @endif
                                    
                                    @if($kidsFunVenue->social_media_handles)
                                        @foreach($kidsFunVenue->social_media_handles as $platform => $url)
                                            @if($url)
                                            <div>
                                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucfirst($platform) }}</label>
                                                <p class="mt-1">
                                                    <a href="{{ $url }}" target="_blank" 
                                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 break-all">
                                                        {{ $url }}
                                                    </a>
                                                </p>
                                            </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">
                            
                            <!-- Venue Info -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Venue Info</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</label>
                                        <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($kidsFunVenue->created_at)->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                                        <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($kidsFunVenue->updated_at)->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Business</label>
                                        <p class="text-gray-900 dark:text-white">{{ $kidsFunVenue->business->name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

