<div class="space-y-6">
    <!-- Error Message -->
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <span class="block sm:inline">{{ $error ?? 'An error occurred while loading the dashboard.' }}</span>
        </div>
    </div>
    
    <!-- Retry Button -->
    <div class="text-center">
        <button wire:click="$refresh" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
            Retry Loading Dashboard
        </button>
    </div>
</div> 