<x-app-layout>
    <div class="py-12" x-data="{ 
        showEventModal: false, 
        showAttendeeModal: false,
        showPaymentModal: false,
        selectedEvent: null,
        selectedAttendee: null
    }" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Program Header -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $program->name }}</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $program->description }}</p>
                        @if($program->image || $program->video)
                            <div class="mt-4">
                                @if($program->image)
                                    <img src="{{ Storage::url($program->image) }}"
                                         alt="{{ $program->name }}"
                                         class="w-full max-w-xl rounded-lg shadow-sm border border-gray-200" />
                                @endif
                                @if($program->video)
                                    <video controls class="w-full max-w-xl rounded-lg shadow-sm border border-gray-200 mt-3">
                                        <source src="{{ Storage::url($program->video) }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                @endif
                            </div>
                        @endif
                        <div class="flex items-center mt-4 space-x-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                Age Group: {{ $program->{'age-group'} }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                {{ $program->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($program->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $events->count() }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Events</div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show"
                    class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 transition"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                    <button @click="show = false"
                        class="absolute top-1 right-2 text-xl font-semibold text-green-700">
                        &times;
                    </button>
                </div>
            @endif

            <!-- Events Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Events</h2>
                    <button @click="showEventModal = true" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Event</span>
                    </button>
                </div>

                @if($events->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Event Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Venue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Event Fee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($events as $event)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" 
                                        @click="selectedEvent = {{ $event->id }}; showAttendeeModal = true">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $event->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $event->start_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $event->location }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $event->currency->symbol ?? '$' }}{{ number_format($event->price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $event->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($event->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No events</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new event.</p>
                    </div>
                @endif
            </div>

            @php
                $organizerBusiness = $events->first()?->business;
                $businessSocials = $organizerBusiness?->social_media_handles;
                if (is_string($businessSocials)) {
                    $decoded = json_decode($businessSocials, true);
                    $businessSocials = is_array($decoded) ? $decoded : [];
                }
                $businessSocials = is_array($businessSocials) ? $businessSocials : [];

                $firstEvent = $events->first();
                $eventSocials = $firstEvent?->social_media_handles ?? [];
                if (is_string($eventSocials)) {
                    $decoded = json_decode($eventSocials, true);
                    $eventSocials = is_array($decoded) ? $decoded : [];
                }
                $eventSocials = is_array($eventSocials) ? $eventSocials : [];
            @endphp

            <!-- Organizer / Social Links -->
            @if($organizerBusiness || $firstEvent)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Organizer & Social Links</h2>
                    
                    @if($organizerBusiness)
                        <div class="mb-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Business</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $organizerBusiness->name }}</div>
                            @if($organizerBusiness->website_link)
                                <a class="text-blue-600 hover:text-blue-800 break-all" href="{{ $organizerBusiness->website_link }}" target="_blank" rel="noreferrer">
                                    {{ $organizerBusiness->website_link }}
                                </a>
                            @endif
                        </div>
                        @if(count($businessSocials) > 0)
                            <div class="flex flex-wrap gap-3">
                                @foreach($businessSocials as $platform => $url)
                                    @if($url)
                                        <a class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-sm text-gray-800"
                                           href="{{ $url }}" target="_blank" rel="noreferrer">
                                            {{ ucfirst($platform) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endif

                    @if($firstEvent)
                        <div class="mt-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Event Organizer Details</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @if($firstEvent->organizer_name)
                                    <div><span class="font-medium">Name:</span> {{ $firstEvent->organizer_name }}</div>
                                @endif
                                @if($firstEvent->organizer_email)
                                    <div><span class="font-medium">Email:</span> {{ $firstEvent->organizer_email }}</div>
                                @endif
                                @if($firstEvent->organizer_phone)
                                    <div><span class="font-medium">Phone:</span> {{ $firstEvent->organizer_phone }}</div>
                                @endif
                                @if($firstEvent->organizer_address)
                                    <div><span class="font-medium">Address:</span> {{ $firstEvent->organizer_address }}</div>
                                @endif
                            </div>
                        </div>

                        @if(count($eventSocials) > 0)
                            <div class="mt-4 flex flex-wrap gap-3">
                                @foreach($eventSocials as $platform => $url)
                                    @if($url)
                                        <a class="px-3 py-1 rounded-full bg-blue-50 hover:bg-blue-100 text-sm text-blue-800"
                                           href="{{ $url }}" target="_blank" rel="noreferrer">
                                            {{ ucfirst($platform) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <!-- Register for Program Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Register for Program</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">Register your child to participate in the full Bible Adventure program.</p>
                    </div>
                    <button @click="showAttendeeModal = true" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Register Child</span>
                    </button>
                </div>
            </div>

            <!-- Registered Children Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Registered Children</h2>
                
                @php
                    $allAttendees = collect();
                    foreach($events as $event) {
                        $allAttendees = $allAttendees->merge($event->attendees);
                    }
                @endphp

                @if($allAttendees->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Child Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Age</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parent/Guardian</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Event Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount Paid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($allAttendees as $index => $attendee)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" 
                                        data-attendee-uuid="{{ $attendee->uuid }}"
                                        onclick="window.location.href='{{ route('programs.show', $attendee->programEvent->program_ids[0] ?? 1) }}'">

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $attendee->child_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->child_age }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->parent_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->parent_phone }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->programEvent->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $attendee->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($attendee->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            ${{ number_format($attendee->total_paid, 2) }}
                                        </td>
                                        <td class="balance-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            ${{ number_format($attendee->balance, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($attendee->payment_status === 'paid')
                                                <span class="status-cell inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Paid
                                                </span>
                                            @elseif($attendee->payment_status === 'partial')
                                                <span class="status-cell inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Partial
                                                </span>
                                            @else
                                                <span class="status-cell inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Unpaid
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div class="flex space-x-2">
                                                <button onclick="event.stopPropagation(); openPaymentModal('{{ $attendee->uuid }}')" 
                                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                                    Record Payment
                                                </button>
                                                <button onclick="event.stopPropagation(); viewPayments('{{ $attendee->uuid }}')" 
                                                        class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                                                    View Payments
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No registered children</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by registering a child for an event.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Add Event Modal -->
        <div x-show="showEventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Add Event</h3>
                        <button @click="showEventModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form action="{{ route('programs.events.store', $program) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Event Name</label>
                                <input type="text" name="name" placeholder="Enter event name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" placeholder="Enter event description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" name="start_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" name="end_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Location</label>
                                <input type="text" name="location" placeholder="Enter event location" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Event Fee</label>
                                    <input type="number" name="price" placeholder="Enter event fee" step="0.01" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Currency</label>
                                    <select name="currency_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Currency</option>
                                        @foreach(\App\Models\Currency::all() as $currency)
                                            <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->symbol }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Event Image</label>
                                <input type="file" name="image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, GIF - Max 2MB</p>
                                @error('image')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Video Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Event Video</label>
                                <input type="file" name="video" accept="video/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">MP4, MOV, AVI - Max 10MB</p>
                                @error('video')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showEventModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Add Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Register Child Modal -->
        <div x-show="showAttendeeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Register Your Child</h3>
                        <button @click="showAttendeeModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form method="POST" x-data="{ selectedEvent: '' }" id="attendeeForm" @submit.prevent="submitForm()">
                        @csrf
                        <input type="hidden" name="program_event_id" x-model="selectedEvent">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Child Full Name</label>
                                <input type="text" name="child_name" placeholder="Enter child's full name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Child Age</label>
                                <input type="number" name="child_age" placeholder="Enter child's age" min="1" max="18" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Parent/Guardian Name</label>
                                <input type="text" name="parent_name" placeholder="Enter parent/guardian name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                <input type="tel" name="parent_phone" placeholder="Enter contact number" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Parent Email</label>
                                <input type="email" name="parent_email" placeholder="Enter parent email (optional)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                                                         <div>
                                 <label class="block text-sm font-medium text-gray-700">Select Event</label>
                                 <select x-model="selectedEvent" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <option value="">Choose an event</option>
                                     @foreach($events as $event)
                                         <option value="{{ $event->uuid }}">{{ $event->name }} - {{ $event->start_date->format('M d, Y') }}</option>
                                     @endforeach
                                 </select>
                             </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender</label>
                                <select name="gender" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                                <select name="payment_method" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="card" disabled>Card (Coming Soon)</option>
                                    <option value="bank_transfer" disabled>Bank Transfer (Coming Soon)</option>
                                    <option value="airtel_money" disabled>Airtel Money (Coming Soon)</option>
                                    <option value="mtn_mobile_money" disabled>MTN Mobile Money (Coming Soon)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showAttendeeModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

            <!-- Payment Modal -->
        <div id="paymentModal" x-show="showPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Record Payment</h3>
                    <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form method="POST" id="paymentForm" onsubmit="return submitPaymentForm()">
                    @csrf
                    <input type="hidden" id="selectedAttendeeUuid" name="attendee_uuid" value="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Amount</label>
                            <input type="number" name="amount" placeholder="Enter payment amount" step="0.01" min="0.01" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select name="payment_method" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select payment method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="airtel_money">Airtel Money</option>
                                <option value="mtn_mobile_money">MTN Mobile Money</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Reference</label>
                            <input type="text" name="payment_reference" placeholder="Enter payment reference (optional)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Date</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" placeholder="Enter any additional notes (optional)" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showPaymentModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function submitForm() {
            const form = document.getElementById('attendeeForm');
            const formData = new FormData(form);
            const selectedEvent = document.querySelector('select[x-model="selectedEvent"]').value;
            
            if (!selectedEvent) {
                alert('Please select an event');
                return;
            }
            
            // Add the selected event to form data
            formData.append('program_event_id', selectedEvent);
            
            fetch(`/events/${selectedEvent}/attendees`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while registering the child. Please try again.');
            });
        }

        let currentAttendeeUuid = null;

        function openPaymentModal(attendeeUuid) {
            currentAttendeeUuid = attendeeUuid;
            document.getElementById('selectedAttendeeUuid').value = attendeeUuid;
            
            // Set Alpine.js variable directly
            const alpineElement = document.querySelector('[x-data]');
            if (alpineElement && alpineElement.__x) {
                alpineElement.__x.$data.showPaymentModal = true;
                alpineElement.__x.$data.selectedAttendee = attendeeUuid;
            } else {
                // Fallback: show modal directly
                const modal = document.getElementById('paymentModal');
                if (modal) {
                    modal.style.display = 'block';
                }
            }
        }

        function submitPaymentForm() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            
            if (!currentAttendeeUuid) {
                alert('Please select an attendee');
                return false;
            }
            
            const url = `/attendees/${currentAttendeeUuid}/payments`;
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                            // Close the payment modal using Alpine.js
        const alpineElement = document.querySelector('[x-data]');
        if (alpineElement && alpineElement.__x) {
            alpineElement.__x.$data.showPaymentModal = false;
        }
                    
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50';
                    successMessage.textContent = 'Payment recorded successfully!';
                    document.body.appendChild(successMessage);
                    
                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                    
                            // Update the attendee's payment status in the table
        updateAttendeePaymentStatus(currentAttendeeUuid, data.new_balance);
        
        // Reset the payment form
        const form = document.getElementById('paymentForm');
        if (form) {
            form.reset();
        }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while recording the payment. Please try again.');
            });
            
            return false;
        }

        function updateAttendeePaymentStatus(attendeeUuid, newBalance) {
            // Find the attendee row and update the balance
            const attendeeRow = document.querySelector(`[data-attendee-uuid="${attendeeUuid}"]`);
            if (attendeeRow) {
                const balanceCell = attendeeRow.querySelector('.balance-cell');
                if (balanceCell) {
                    balanceCell.textContent = `$${newBalance}`;
                }
                
                // Update payment status
                const statusCell = attendeeRow.querySelector('.status-cell');
                if (statusCell) {
                    if (newBalance <= 0) {
                        statusCell.textContent = 'Paid';
                        statusCell.className = 'status-cell px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium';
                    } else {
                        statusCell.textContent = 'Partial';
                        statusCell.className = 'status-cell px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-medium';
                    }
                }
            }
        }

        function viewPayments(attendeeId) {
            fetch(`/attendees/${attendeeId}/payments`)
            .then(response => response.json())
            .then(data => {
                // Create a modal to show payment history
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
                modal.innerHTML = `
                    <div class="relative top-20 mx-auto p-5 border w-3/4 max-w-4xl shadow-lg rounded-md bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Payment History - ${data.attendee.child_name}</h3>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-4">
                            <p><strong>Total Due:</strong> $${data.attendee.amount_due}</p>
                            <p><strong>Total Paid:</strong> $${data.total_paid}</p>
                            <p><strong>Balance:</strong> $${data.balance}</p>
                            <p><strong>Status:</strong> ${data.payment_status}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${data.payments.map(payment => `
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${payment.formatted_payment_date}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${payment.amount}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${payment.payment_method_display}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${payment.payment_reference || '-'}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${payment.notes || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Close
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading payment history.');
            });
        }
    </script>
</x-app-layout> 