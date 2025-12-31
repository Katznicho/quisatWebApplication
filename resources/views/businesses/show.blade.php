<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- Header Section -->
            <div class="mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="mb-6 lg:mb-0">
                            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                                My Business
                            </h1>
                            <p class="text-gray-600">
                                View and manage your business information
                            </p>
                        </div>
                        
                        <!-- Business Logo -->
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-gray-200 shadow-lg bg-white mb-4">
                                @if ($business->logo && file_exists(public_path('storage/' . $business->logo)))
                                    <img src="{{ asset('storage/' . $business->logo) }}" alt="Business Logo"
                                        class="w-full h-full object-cover">
                                @else
                                    <img src="{{ asset('images/logo.png') }}" alt="Default Logo"
                                        class="w-full h-full object-cover">
                                @endif
                            </div>
                            <button onclick="openLogoModal()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                Update Logo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Information Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                <!-- Basic Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Basic Information
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Business Name</label>
                            <p class="text-gray-900 font-semibold">{{ $business->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Email</label>
                            <p class="text-gray-900">{{ $business->email }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Phone</label>
                            <p class="text-gray-900">{{ $business->phone }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Number</label>
                            <p class="text-gray-900 font-mono">{{ $business->account_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Location
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Address</label>
                            <p class="text-gray-900">{{ $business->address }}</p>
                        </div>
                        @if($business->shop_number)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Shop Number</label>
                            <p class="text-gray-900">{{ $business->shop_number }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="text-sm font-medium text-gray-500">City</label>
                            <p class="text-gray-900">{{ $business->city }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Country</label>
                            <p class="text-gray-900">{{ $business->country }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Business Category</label>
                            <p class="text-gray-900">{{ $business->businessCategory->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media & Website -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        Social Media & Website
                    </h3>
                    <button onclick="openSocialMediaModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $socialHandles = $business->social_media_handles ?? [];
                    @endphp
                    @if($business->website_link)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Website</label>
                        <p class="text-gray-900">
                            <a href="{{ $business->website_link }}" target="_blank" rel="noopener noreferrer" 
                                class="text-blue-600 hover:text-blue-800 underline break-all">
                                {{ $business->website_link }}
                            </a>
                        </p>
                    </div>
                    @endif
                    @if(!empty($socialHandles['facebook']))
                    <div>
                        <label class="text-sm font-medium text-gray-500">Facebook</label>
                        <p class="text-gray-900">
                            <a href="{{ $socialHandles['facebook'] }}" target="_blank" rel="noopener noreferrer" 
                                class="text-blue-600 hover:text-blue-800 underline break-all">
                                {{ $socialHandles['facebook'] }}
                            </a>
                        </p>
                    </div>
                    @endif
                    @if(!empty($socialHandles['instagram']))
                    <div>
                        <label class="text-sm font-medium text-gray-500">Instagram</label>
                        <p class="text-gray-900">
                            <a href="{{ $socialHandles['instagram'] }}" target="_blank" rel="noopener noreferrer" 
                                class="text-blue-600 hover:text-blue-800 underline break-all">
                                {{ $socialHandles['instagram'] }}
                            </a>
                        </p>
                    </div>
                    @endif
                    @if(!empty($socialHandles['twitter']))
                    <div>
                        <label class="text-sm font-medium text-gray-500">Twitter/X</label>
                        <p class="text-gray-900">
                            <a href="{{ $socialHandles['twitter'] }}" target="_blank" rel="noopener noreferrer" 
                                class="text-blue-600 hover:text-blue-800 underline break-all">
                                {{ $socialHandles['twitter'] }}
                            </a>
                        </p>
                    </div>
                    @endif
                    @if(!empty($socialHandles['whatsapp']))
                    <div>
                        <label class="text-sm font-medium text-gray-500">WhatsApp</label>
                        <p class="text-gray-900">
                            @if(str_starts_with($socialHandles['whatsapp'], 'http'))
                                <a href="{{ $socialHandles['whatsapp'] }}" target="_blank" rel="noopener noreferrer" 
                                    class="text-blue-600 hover:text-blue-800 underline break-all">
                                    {{ $socialHandles['whatsapp'] }}
                                </a>
                            @else
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $socialHandles['whatsapp']) }}" target="_blank" rel="noopener noreferrer" 
                                    class="text-blue-600 hover:text-blue-800 underline">
                                    {{ $socialHandles['whatsapp'] }}
                                </a>
                            @endif
                        </p>
                    </div>
                    @endif
                    @if(empty($business->website_link) && empty($socialHandles))
                    <div class="col-span-full text-center py-4">
                        <p class="text-gray-500">No social media links or website added yet. Click "Edit" to add them.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Features Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Enabled Features
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @if($business->enabled_feature_ids && count($business->enabled_feature_ids) > 0)
                        @php
                            $features = \App\Models\Feature::whereIn('id', $business->enabled_feature_ids)->get();
                        @endphp
                        @foreach($features as $feature)
                            <div class="flex items-center p-3 bg-green-50 rounded-lg border border-green-200">
                                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-green-800 font-medium">{{ $feature->name }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="col-span-full text-center py-8">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">No features enabled yet</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Account Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Account Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-gray-900">{{ $business->created_at->format('F j, Y') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="text-gray-900">{{ $business->updated_at->format('F j, Y') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Logo Update Modal -->
    <div id="logoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Update Business Logo</h3>
                    <button onclick="closeLogoModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="{{ route('businesses.update-logo', $business) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select New Logo</label>
                        <input type="file" name="logo" accept="image/*" required 
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">Supported formats: JPEG, PNG, WebP, GIF (Max: 1MB)</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeLogoModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Update Logo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Social Media & Website Update Modal -->
    <div id="socialMediaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border max-w-2xl w-full shadow-lg rounded-md bg-white m-4">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Update Social Media & Website</h3>
                    <button onclick="closeSocialMediaModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="{{ route('businesses.update-social-media', $business) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    @php
                        $socialHandles = $business->social_media_handles ?? [];
                    @endphp
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Website URL <span class="text-gray-400">(Optional)</span></label>
                            <input type="url" name="website_link" value="{{ old('website_link', $business->website_link) }}" 
                                placeholder="https://www.example.com"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('website_link')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Facebook URL <span class="text-gray-400">(Optional)</span></label>
                            <input type="url" name="social_facebook" value="{{ old('social_facebook', $socialHandles['facebook'] ?? '') }}" 
                                placeholder="https://facebook.com/yourbusiness"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('social_facebook')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instagram URL <span class="text-gray-400">(Optional)</span></label>
                            <input type="url" name="social_instagram" value="{{ old('social_instagram', $socialHandles['instagram'] ?? '') }}" 
                                placeholder="https://instagram.com/yourbusiness"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('social_instagram')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Twitter/X URL <span class="text-gray-400">(Optional)</span></label>
                            <input type="url" name="social_twitter" value="{{ old('social_twitter', $socialHandles['twitter'] ?? '') }}" 
                                placeholder="https://twitter.com/yourbusiness"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('social_twitter')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp <span class="text-gray-400">(Optional)</span></label>
                            <input type="text" name="social_whatsapp" value="{{ old('social_whatsapp', $socialHandles['whatsapp'] ?? '') }}" 
                                placeholder="+1234567890 or URL"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Enter phone number (e.g., +1234567890) or WhatsApp URL</p>
                            @error('social_whatsapp')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeSocialMediaModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openLogoModal() {
            document.getElementById('logoModal').classList.remove('hidden');
        }
        
        function closeLogoModal() {
            document.getElementById('logoModal').classList.add('hidden');
        }
        
        function openSocialMediaModal() {
            document.getElementById('socialMediaModal').classList.remove('hidden');
        }
        
        function closeSocialMediaModal() {
            document.getElementById('socialMediaModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('logoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoModal();
            }
        });
        
        document.getElementById('socialMediaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSocialMediaModal();
            }
        });
    </script>
</x-app-layout>