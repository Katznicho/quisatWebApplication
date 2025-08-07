<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Impersonation Banner -->
            @impersonating
                <div class="bg-amber-100 border-l-4 border-amber-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-amber-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-amber-800 font-medium">
                                You are currently impersonating <strong>{{ auth()->user()->name }}</strong>
                            </span>
                        </div>
                        <a href="{{ route('impersonate.leave') }}"
                            class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Stop Impersonating
                        </a>
                    </div>
                </div>
            @endImpersonating

            <!-- Header Section -->
            <div class="mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="mb-6 lg:mb-0">
                            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                                Dashboard
                            </h1>
                            <div class="flex flex-wrap items-center gap-4 text-gray-600">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="font-medium">{{ Auth::user()->name }}</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="font-medium">{{ $business->name ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-4 text-white">
                                <div class="text-sm font-medium opacity-90">Current Date</div>
                                <div class="text-lg font-semibold">{{ now()->format('l, F j, Y') }}</div>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-4 text-white">
                                <div class="text-sm font-medium opacity-90">Current Time</div>
                                <div class="text-lg font-semibold font-mono">{{ now()->format('H:i:s') }} UTC</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div >
                @livewire('dashboard')
            </div>

        </div>
    </div>
</x-app-layout>
