<x-app-layout>
    <div class="py-12 bg-gradient-to-b from-[#011478]/10 to-transparent">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Impersonation Banner -->
            @impersonating
                <div
                    class="bg-yellow-500/80 backdrop-blur-sm text-white p-4 text-center flex justify-between items-center mx-auto max-w-7xl sm:px-6 lg:px-8 rounded-xl shadow-sm mb-4">
                    <span class="font-medium text-lg">
                        You are currently impersonating <strong>{{ auth()->user()->name }}</strong>.
                    </span>
                    <a href="{{ route('impersonate.leave') }}"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition duration-200">
                        Stop Impersonating
                    </a>
                </div>
            @endImpersonating

            <!-- Page Title with User & Branch Info -->
            <div class="mb-8 flex justify-between items-center bg-white/50 backdrop-blur-sm p-6 rounded-xl shadow-sm">
                <div>
                    <h2 class="text-3xl font-bold text-[#011478]">Welcome to {{ $business->name ?? 'N/A' }}</h2>
                    <div class="flex items-center mt-2 space-x-4">
                        <p class="text-gray-600 font-medium">User: {{ Auth::user()->name }}</p>



                        <span class="text-gray-400">|</span>
                        <p class="text-gray-600 font-medium">{{ now()->format('l, F j, Y') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-[#011478]/80">Current Time</p>
                    <p class="text-lg font-mono">{{ now()->format('H:i:s') }} UTC</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-sm p-6">
                <!-- Dashboard Content -->
                @livewire('dashboard')
            </div>
        </div>
    </div>







</x-app-layout>
