<div class="space-y-6">
    
    <!-- Dashboard Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Overview</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Last updated: {{ $lastUpdate }}</p>
        </div>
        <button wire:click="$refresh" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-medium transition-colors duration-200">
            <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span wire:loading.remove>Refresh Data</span>
            <span wire:loading>Refreshing...</span>
        </button>
    </div>
    
    <!-- Error Messages -->
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif
    
    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Category Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select wire:model="selectedCategory" id="category" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Country Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                <select wire:model="selectedCountry" id="country" class="w-full rounded-lg border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 bg-blue-50 dark:bg-blue-900/20">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}">{{ $country }}</option>
                    @endforeach
                </select>
            </div>

            <!-- District/State Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="district" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">District/State</label>
                <select wire:model="selectedDistrict" id="district" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Districts/States</option>
                    @foreach($districts as $district)
                        <option value="{{ $district }}">{{ $district }}</option>
                    @endforeach
                </select>
            </div>

            <!-- From Date -->
            <div class="flex-1 min-w-[150px]">
                <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                <div class="relative">
                    <input type="text" wire:model="fromDate" id="from_date" placeholder="dd/mm/yyyy" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 pr-10">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- To Date -->
            <div class="flex-1 min-w-[150px]">
                <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                <div class="relative">
                    <input type="text" wire:model="toDate" id="to_date" placeholder="dd/mm/yyyy" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 pr-10">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Apply Filters Button -->
            <div class="flex items-end gap-2">
                <button wire:click="applyFilters" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <span wire:loading.remove>Apply Filters</span>
                    <span wire:loading>Loading...</span>
                </button>
                <button wire:click="resetFilters" wire:loading.attr="disabled" class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Data Cards -->
    @if(auth()->user()->business_id == 1)
        <!-- Admin Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Total Businesses Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300">TOTAL BUSINESSES</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-blue-900 dark:text-white">
                        {{ number_format($totalBusinesses) }}
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    ↑ {{ $businessesChange }}% from last month
                </div>
            </div>

        <!-- Total Users Card -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl shadow-lg p-6 border border-green-200 dark:border-green-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-500 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-green-700 dark:text-green-300">TOTAL USERS</h3>
                </div>
            </div>
            <div class="flex items-baseline mb-3">
                <span class="text-3xl font-bold text-green-900 dark:text-white">
                    {{ number_format($totalUsers) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $usersChange }}% from last month
            </div>
        </div>

        <!-- Active Business Clients Card -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl shadow-lg p-6 border border-purple-200 dark:border-purple-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-500 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300">ACTIVE CLIENTS</h3>
                </div>
            </div>
            <div class="flex items-baseline mb-3">
                <span class="text-3xl font-bold text-purple-900 dark:text-white">
                    {{ number_format($activeBusinessClients) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $clientsChange }}% from last month
            </div>
        </div>

        <!-- Active Business Staff Card -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl shadow-lg p-6 border border-orange-200 dark:border-orange-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-orange-500 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-300">ACTIVE STAFF</h3>
                </div>
            </div>
            <div class="flex items-baseline mb-3">
                <span class="text-3xl font-bold text-orange-900 dark:text-white">
                    {{ number_format($activeBusinessStaff) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $staffChange }}% from last month
            </div>
        </div>

    </div>
    @else
        <!-- Business Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Total Students Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300">TOTAL STUDENTS</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-blue-900 dark:text-white">
                        {{ number_format($totalStudents) }}
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    ↑ {{ $studentsChange }}% from last month
                </div>
            </div>

            <!-- Total Teachers Card -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl shadow-lg p-6 border border-green-200 dark:border-green-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-green-700 dark:text-green-300">TOTAL TEACHERS</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-green-900 dark:text-white">
                        {{ number_format($totalTeachers) }}
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    ↑ {{ $teachersChange }}% from last month
                </div>
            </div>

            <!-- Total Classes Card -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl shadow-lg p-6 border border-purple-200 dark:border-purple-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300">TOTAL CLASSES</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-purple-900 dark:text-white">
                        {{ number_format($totalClasses) }}
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    ↑ {{ $classesChange }}% from last month
                </div>
            </div>

            <!-- Attendance Rate Card -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl shadow-lg p-6 border border-orange-200 dark:border-orange-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-orange-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-300">ATTENDANCE RATE</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-orange-900 dark:text-white">
                        {{ $attendanceRate }}%
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    Excellent attendance
                </div>
            </div>

        </div>

        <!-- Additional Business Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
            
            <!-- Total Subjects Card -->
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-xl shadow-lg p-6 border border-indigo-200 dark:border-indigo-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-indigo-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">TOTAL SUBJECTS</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-indigo-900 dark:text-white">
                        {{ number_format($totalSubjects) }}
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    ↑ {{ $subjectsChange }}% from last month
                </div>
            </div>

            <!-- Total Exams Card -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl shadow-lg p-6 border border-red-200 dark:border-red-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-red-700 dark:text-red-300">TOTAL EXAMS</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-red-900 dark:text-white">
                        {{ number_format($totalExams) }}
                    </span>
                </div>
                <div class="flex items-center text-blue-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    Scheduled this term
                </div>
            </div>

            <!-- Total Parents Card -->
            <div class="bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-900/20 dark:to-teal-800/20 rounded-xl shadow-lg p-6 border border-teal-200 dark:border-teal-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-teal-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-teal-700 dark:text-teal-300">TOTAL PARENTS</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-teal-900 dark:text-white">
                        {{ number_format($totalParents) }}
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    Registered parents
                </div>
            </div>

            <!-- Average Grade Card -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl shadow-lg p-6 border border-yellow-200 dark:border-yellow-700 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-500 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-yellow-700 dark:text-yellow-300">AVERAGE GRADE</h3>
                    </div>
                </div>
                <div class="flex items-baseline mb-3">
                    <span class="text-3xl font-bold text-yellow-900 dark:text-white">
                        {{ $averageGrade }}%
                    </span>
                </div>
                <div class="flex items-center text-green-600 text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                    Above average
                </div>
            </div>

        </div>
    @endif

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- New Users Line Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">New Users</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="newUsersChart" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- User Distribution Pie Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">User Distribution</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="userDistributionChart" width="400" height="300"></canvas>
            </div>
        </div>

    </div>

    <!-- System Health and User Role Distribution Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- System Health -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">System Health</h3>
            <div class="space-y-4">
                <!-- Server Status -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">Server Status</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['server']['status'] ?? 'Online' }} ({{ $systemHealth['server']['uptime'] ?? '99.98%' }} uptime)</span>
                </div>

                <!-- Database -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">Database</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['database']['status'] ?? 'Operational' }}</span>
                </div>

                <!-- Storage -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">Storage</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['storage']['status'] ?? '78%' }} ({{ $systemHealth['storage']['warning'] ?? 'Warning' }})</span>
                </div>

                <!-- API Services -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">API Services</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['api_services']['status'] ?? 'All Operational' }}</span>
                </div>
            </div>
        </div>

        <!-- User Role Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">User Role Distribution</h3>
            <div class="relative" style="height: 200px;">
                <canvas id="userRoleDistributionChart" width="400" height="200"></canvas>
            </div>
        </div>

    </div>

</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function initializeCharts() {
    // Simple check for Chart.js
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }
    // New Users Line Chart
    try {
        const newUsersCtx = document.getElementById('newUsersChart');
        if (!newUsersCtx) {
            console.error('newUsersChart element not found');
            return;
        }
        const ctx = newUsersCtx.getContext('2d');
        const newUsersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($newUsersChartData['labels'] ?? []),
            datasets: [{
                label: 'New Users',
                data: @json($newUsersChartData['data'] ?? []),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        stepSize: 500,
                        maxTicksLimit: 10
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#3B82F6'
                }
            }
        }
    });
    } catch (error) {
        console.error('Error creating new users chart:', error);
    }

    // User Distribution Pie Chart
    try {
        const userDistributionCtx = document.getElementById('userDistributionChart');
        if (!userDistributionCtx) {
            console.error('userDistributionChart element not found');
            return;
        }
        const ctx = userDistributionCtx.getContext('2d');
        const userDistributionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: @json($userDistributionChartData['labels'] ?? []),
            datasets: [{
                data: @json($userDistributionChartData['data'] ?? []),
                backgroundColor: @json($userDistributionChartData['colors'] ?? []),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });
    } catch (error) {
        console.error('Error creating user distribution chart:', error);
    }

    // User Role Distribution Pie Chart
    try {
        const userRoleDistributionCtx = document.getElementById('userRoleDistributionChart');
        if (!userRoleDistributionCtx) {
            console.error('userRoleDistributionChart element not found');
            return;
        }
        const ctx = userRoleDistributionCtx.getContext('2d');
        const userRoleDistributionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: @json($userRoleDistributionData['labels'] ?? []),
            datasets: [{
                data: @json($userRoleDistributionData['data'] ?? []),
                backgroundColor: @json($userRoleDistributionData['colors'] ?? []),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
    } catch (error) {
        console.error('Error creating user role distribution chart:', error);
    }

    // Update charts when Livewire updates
    try {
        window.addEventListener('livewire:load', function () {
            Livewire.on('chartsUpdated', function () {
            // Update line chart
            newUsersChart.data.labels = @json($newUsersChartData['labels'] ?? []);
            newUsersChart.data.datasets[0].data = @json($newUsersChartData['data'] ?? []);
            newUsersChart.update();

            // Update pie chart
            userDistributionChart.data.labels = @json($userDistributionChartData['labels'] ?? []);
            userDistributionChart.data.datasets[0].data = @json($userDistributionChartData['data'] ?? []);
            userDistributionChart.data.datasets[0].backgroundColor = @json($userDistributionChartData['colors'] ?? []);
            userDistributionChart.update();

            // Update user role distribution chart
            userRoleDistributionChart.data.labels = @json($userRoleDistributionData['labels'] ?? []);
            userRoleDistributionChart.data.datasets[0].data = @json($userRoleDistributionData['data'] ?? []);
            userRoleDistributionChart.data.datasets[0].backgroundColor = @json($userRoleDistributionData['colors'] ?? []);
            userRoleDistributionChart.update();
            });
        });
    } catch (error) {
        console.error('Error setting up Livewire chart updates:', error);
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeCharts);
</script>
