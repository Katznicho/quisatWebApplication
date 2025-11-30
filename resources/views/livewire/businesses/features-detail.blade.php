<div class="space-y-6">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                    {{ count($features) }} Feature{{ count($features) !== 1 ? 's' : '' }} Enabled
                </h3>
                @if($business)
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Business: <span class="font-medium">{{ $business->name }}</span>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($features as $feature)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $feature->name }}
                            </h4>
                        </div>
                        
                        @if($feature->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">
                                {{ $feature->description }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-500 italic mt-2">
                                No description available
                            </p>
                        @endif
                    </div>
                </div>
                
                @if($feature->price && $feature->price > 0)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Price:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $feature->currency->symbol ?? '' }}{{ number_format($feature->price, 2) }}
                                @if($feature->currency)
                                    <span class="text-xs text-gray-500 dark:text-gray-500 ml-1">
                                        ({{ $feature->currency->code }})
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

