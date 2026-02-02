<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $parentCorner->title }}</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Event Details & Analytics</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('parent-corners.edit', $parentCorner) }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </a>
                            <a href="{{ route('parent-corners.index') }}" 
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
                    <!-- Success/Error Messages -->
                    @if(session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                    @endif
                    
                    @if(session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                    @endif
                    
                    @if($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <!-- Event Details -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Basic Information -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</label>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $parentCorner->title }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $parentCorner->status_badge_color }}">
                                            {{ ucfirst($parentCorner->status) }}
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Price</label>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $parentCorner->formatted_price }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</label>
                                        <p class="text-gray-900 dark:text-white">{{ $parentCorner->location ?? 'Not specified' }}</p>
                                    </div>
                                </div>
                                
                                @if($parentCorner->description)
                                <div class="mt-6">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                                    <p class="text-gray-900 dark:text-white mt-2">{{ $parentCorner->description }}</p>
                                </div>
                                @endif
                            </div>

                            <!-- Event Image -->
                            @if($parentCorner->image_url)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event Image</h2>
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $parentCorner->image_url) }}" alt="{{ $parentCorner->title }}" 
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
                                        <p class="text-gray-900 dark:text-white">{{ $parentCorner->start_date->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date & Time</label>
                                        <p class="text-gray-900 dark:text-white">{{ $parentCorner->end_date->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            @if($parentCorner->contact_email || $parentCorner->contact_phone || $parentCorner->contact_info)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @if($parentCorner->contact_email)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                                        <a href="mailto:{{ $parentCorner->contact_email }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $parentCorner->contact_email }}
                                        </a>
                                    </div>
                                    @endif
                                    
                                    @if($parentCorner->contact_phone)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</label>
                                        <a href="tel:{{ $parentCorner->contact_phone }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $parentCorner->contact_phone }}
                                        </a>
                                    </div>
                                    @endif
                                    
                                    @if($parentCorner->contact_info)
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Additional Info</label>
                                        <p class="text-gray-900 dark:text-white mt-1">{{ $parentCorner->contact_info }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Register Parent Section -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Register a Parent</h2>
                                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm">Add a new parent registration for this event</p>
                                    </div>
                                </div>
                                
                                <form method="POST" action="{{ route('parent-corner-registrations.store', $parentCorner->id) }}" class="space-y-4">
                                    @csrf
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="parent_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent Name *</label>
                                            <input type="text" id="parent_name" name="parent_name" value="{{ old('parent_name') }}" 
                                                   placeholder="Enter parent's full name"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('parent_name') border-red-500 @enderror" required>
                                            @error('parent_name')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div>
                                            <label for="parent_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                                            <input type="email" id="parent_email" name="parent_email" value="{{ old('parent_email') }}" 
                                                   placeholder="parent@example.com"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('parent_email') border-red-500 @enderror" required>
                                            @error('parent_email')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div>
                                            <label for="parent_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone *</label>
                                            <input type="tel" id="parent_phone" name="parent_phone" value="{{ old('parent_phone') }}" 
                                                   placeholder="+256 700 000 000"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('parent_phone') border-red-500 @enderror" required>
                                            @error('parent_phone')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div>
                                            <label for="parent_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                                            <input type="text" id="parent_address" name="parent_address" value="{{ old('parent_address') }}" 
                                                   placeholder="Parent's address"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('parent_address') border-red-500 @enderror">
                                            @error('parent_address')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div>
                                            <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
                                            <select id="payment_method" name="payment_method" 
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('payment_method') border-red-500 @enderror" 
                                                   required>
                                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                                <option value="airtel_money" {{ old('payment_method') === 'airtel_money' ? 'selected' : '' }}>Airtel Money</option>
                                                <option value="mtn_mobile_money" {{ old('payment_method') === 'mtn_mobile_money' ? 'selected' : '' }}>MTN Mobile Money</option>
                                                <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                            @error('payment_method')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label for="interests" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Interests</label>
                                            <textarea id="interests" name="interests" rows="2" 
                                                      placeholder="Topics of interest or special requirements"
                                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('interests') border-red-500 @enderror">{{ old('interests') }}</textarea>
                                            @error('interests')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                            <textarea id="notes" name="notes" rows="2" 
                                                      placeholder="Additional notes or comments"
                                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end pt-4 border-t border-blue-200 dark:border-blue-800">
                                        <button type="submit" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span>Register Parent</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Registered Parents -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                    Registered Parents ({{ $parentCorner->registrations->count() }})
                                </h2>
                                
                                @if($parentCorner->registrations->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                        <thead class="bg-gray-100 dark:bg-gray-800">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Parent Name</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
                                            @foreach($parentCorner->registrations as $registration)
                                            <tr>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $registration->parent_name }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    <a href="mailto:{{ $registration->parent_email }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                        {{ $registration->parent_email }}
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    <a href="tel:{{ $registration->parent_phone }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                        {{ $registration->parent_phone }}
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $registration->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                        {{ ucfirst($registration->payment_method) }} - {{ ucfirst($registration->payment_status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $registration->registration_status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                        {{ ucfirst($registration->registration_status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                    <div class="flex items-center space-x-2">
                                                        <button onclick="viewRegistrationDetails('{{ $registration->uuid }}')" 
                                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" 
                                                                title="View Details">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                        </button>
                                                        <form method="POST" action="{{ route('parent-corner-registrations.destroy', $registration->uuid) }}" class="inline" 
                                                              onsubmit="return confirm('Are you sure you want to delete this registration?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Delete">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">No parents registered yet.</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Analytics Sidebar -->
                        <div class="space-y-6">
                            
                            <!-- Participant Metrics -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Participants</h2>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Current Participants</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $parentCorner->current_participants }}</span>
                                    </div>
                                    
                                    @if($parentCorner->max_participants)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Maximum Participants</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $parentCorner->max_participants }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Available Spots</span>
                                        <span class="text-lg font-semibold {{ $parentCorner->spots_available > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $parentCorner->spots_available }}
                                        </span>
                                    </div>
                                    @else
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Capacity</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Unlimited</span>
                                    </div>
                                    @endif
                                    
                                    @if($parentCorner->is_full)
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
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Featured Event</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $parentCorner->is_featured ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $parentCorner->is_featured ? 'Yes' : 'No' }}
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
                                        <p class="text-gray-900 dark:text-white">{{ $parentCorner->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                                        <p class="text-gray-900 dark:text-white">{{ $parentCorner->updated_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</label>
                                        <p class="text-gray-900 dark:text-white">{{ $parentCorner->creator->name ?? 'Unknown' }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Event ID</label>
                                        <p class="text-gray-900 dark:text-white font-mono text-sm">#{{ $parentCorner->id }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                                
                                <div class="space-y-3">
                                    <a href="{{ route('parent-corners.edit', $parentCorner) }}" 
                                       class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center block">
                                        Edit Event
                                    </a>
                                    
                                    <button onclick="toggleFeatured({{ $parentCorner->id }})" 
                                            class="w-full {{ $parentCorner->is_featured ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg">
                                        {{ $parentCorner->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}
                                    </button>
                                    
                                    <form method="POST" action="{{ route('parent-corners.destroy', $parentCorner) }}" class="inline w-full" 
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
                fetch(`/parent-corners/${id}/toggle-featured`, {
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

        // Store registration data
        const registrations = @json($registrationsData);

        function viewRegistrationDetails(uuid) {
            const registration = registrations.find(r => r.uuid === uuid);
            if (!registration) return;

            const escapeHtml = (text) => {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };

            let detailsHtml = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
            
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Parent Name</label><p class="text-gray-900 dark:text-white font-medium mt-1">' + escapeHtml(registration.parent_name) + '</p></div>';
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label><p class="text-gray-900 dark:text-white mt-1"><a href="mailto:' + escapeHtml(registration.parent_email) + '" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">' + escapeHtml(registration.parent_email) + '</a></p></div>';
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</label><p class="text-gray-900 dark:text-white mt-1"><a href="tel:' + escapeHtml(registration.parent_phone) + '" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">' + escapeHtml(registration.parent_phone) + '</a></p></div>';
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</label><p class="text-gray-900 dark:text-white mt-1">' + (escapeHtml(registration.parent_address) || 'Not provided') + '</p></div>';
            
            if (registration.number_of_children) {
                detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Number of Children</label><p class="text-gray-900 dark:text-white mt-1">' + registration.number_of_children + '</p></div>';
            }
            
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Method</label><p class="text-gray-900 dark:text-white mt-1 capitalize">' + escapeHtml(registration.payment_method).replace(/_/g, ' ') + '</p></div>';
            
            const paymentStatusClass = registration.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Status</label><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 ' + paymentStatusClass + '">' + (registration.payment_status.charAt(0).toUpperCase() + registration.payment_status.slice(1)) + '</span></div>';
            
            const regStatusClass = registration.registration_status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Status</label><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 ' + regStatusClass + '">' + (registration.registration_status.charAt(0).toUpperCase() + registration.registration_status.slice(1)) + '</span></div>';
            detailsHtml += '<div><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered On</label><p class="text-gray-900 dark:text-white mt-1">' + escapeHtml(registration.created_at) + '</p></div>';
            
            detailsHtml += '</div>';
            
            if (registration.interests) {
                detailsHtml += '<div class="mt-4"><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Interests</label><p class="text-gray-900 dark:text-white mt-1">' + escapeHtml(registration.interests) + '</p></div>';
            }
            
            if (registration.notes) {
                detailsHtml += '<div class="mt-4"><label class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</label><p class="text-gray-900 dark:text-white mt-1">' + escapeHtml(registration.notes) + '</p></div>';
            }

            document.getElementById('registrationDetails').innerHTML = detailsHtml;
            document.getElementById('registrationModal').classList.remove('hidden');
        }

        function closeRegistrationModal() {
            document.getElementById('registrationModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('registrationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRegistrationModal();
            }
        });
    </script>

    <!-- Registration Details Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Parent Registration Details</h3>
                <button onclick="closeRegistrationModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="registrationDetails" class="mt-4 space-y-4">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</x-app-layout>










