<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ __('Kids Fun Venues') }}
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage fun venues for kids</p>
                    </div>
                    <a href="{{ route('kids-fun-venues.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Venue</span>
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                @if($venues->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                        @foreach($venues as $venue)
                            <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                                @if($venue->images && count($venue->images) > 0)
                                    <img src="{{ Storage::url($venue->images[0]) }}" alt="{{ $venue->name }}" class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg mb-2">{{ $venue->name }}</h3>
                                    <p class="text-gray-600 text-sm mb-2">{{ $venue->location }}</p>
                                    <p class="text-sm text-gray-500 mb-2">{{ $venue->open_time }} - {{ $venue->close_time }}</p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $venue->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucfirst($venue->status) }}
                                    </span>
                                    <div class="flex space-x-2 mt-4">
                                        <a href="{{ route('kids-fun-venues.show', $venue) }}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded">View</a>
                                        <a href="{{ route('kids-fun-venues.edit', $venue) }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white text-center py-2 rounded">Edit</a>
                                        <form action="{{ route('kids-fun-venues.destroy', $venue) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-6 py-4">
                        {{ $venues->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <p class="text-gray-500 mb-4">No venues found. Create your first venue!</p>
                        <a href="{{ route('kids-fun-venues.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-block">Add Venue</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

