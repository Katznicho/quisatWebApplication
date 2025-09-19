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

    <script>
        function openLogoModal() {
            document.getElementById('logoModal').classList.remove('hidden');
        }
        
        function closeLogoModal() {
            document.getElementById('logoModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('logoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoModal();
            }
        });
    </script>
</x-app-layout>