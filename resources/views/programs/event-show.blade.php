<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{ route('programs.show', $program) }}" 
                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Program
                </a>
            </div>

            <!-- Event Header -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $event->name }}</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $event->description }}</p>
                        
                        @if($event->image || $event->video)
                            <div class="mt-4">
                                @if($event->image)
                                    <img src="{{ Storage::url($event->image) }}"
                                         alt="{{ $event->name }}"
                                         class="w-full max-w-2xl rounded-lg shadow-sm border border-gray-200" />
                                @endif
                                @if($event->video)
                                    <video controls class="w-full max-w-2xl rounded-lg shadow-sm border border-gray-200 mt-3">
                                        <source src="{{ Storage::url($event->video) }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center mt-4 space-x-4 flex-wrap">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ $event->start_date->format('M d, Y') }}
                                    @if($event->end_date && $event->end_date->format('Y-m-d') !== $event->start_date->format('Y-m-d'))
                                        - {{ $event->end_date->format('M d, Y') }}
                                    @endif
                                </span>
                            </div>
                            
                            @if($event->location)
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $event->location }}</span>
                            </div>
                            @endif

                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400 font-semibold">
                                    {{ $event->currency->symbol ?? '$' }}{{ number_format($event->price, 2) }}
                                </span>
                            </div>

                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                {{ $event->status === 'open' || $event->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($event->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Details -->
            @if($event->organizer_name || $event->organizer_email || $event->organizer_phone || $event->organizer_address)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Organizer Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($event->organizer_name)
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Name:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ $event->organizer_name }}</span>
                        </div>
                    @endif
                    @if($event->organizer_email)
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                            <a href="mailto:{{ $event->organizer_email }}" class="ml-2 text-blue-600 hover:text-blue-800">{{ $event->organizer_email }}</a>
                        </div>
                    @endif
                    @if($event->organizer_phone)
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Phone:</span>
                            <a href="tel:{{ $event->organizer_phone }}" class="ml-2 text-blue-600 hover:text-blue-800">{{ $event->organizer_phone }}</a>
                        </div>
                    @endif
                    @if($event->organizer_address)
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Address:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ $event->organizer_address }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Registered Attendees -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Registered Attendees ({{ $event->attendees->count() }})</h2>
                
                @if($event->attendees->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Child Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Age</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parent/Guardian</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($event->attendees as $attendee)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $attendee->child_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->child_age }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->parent_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $attendee->parent_phone }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $attendee->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($attendee->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($attendee->payment_status === 'paid')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Paid
                                                </span>
                                            @elseif($attendee->payment_status === 'partial')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Partial
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Unpaid
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-600 dark:text-gray-400">No attendees registered yet for this event.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

