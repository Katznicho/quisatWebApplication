<div class="flex h-screen bg-gray-50" x-data="{ mobileMenuOpen: false }">
    <!-- Mobile Sidebar Overlay -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-primary-900 bg-opacity-75 z-40 md:hidden"></div>

    <!-- Sidebar -->
    <aside :class="{'translate-x-0': mobileMenuOpen, '-translate-x-full': !mobileMenuOpen}"
           class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out md:translate-x-0 md:relative md:transform-none md:flex md:flex-shrink-0"
           style="width: 256px; background-color: #322f90;">
        <div class="flex flex-col w-full h-full" style="background-color: #322f90;">
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                <div class="flex items-center justify-between flex-shrink-0 px-4 mb-8">
                    <a href="/" class="flex items-center">
                        <img src="{{ asset('images/stabex_logo_big.jpg') }}" alt="Stabex Logo" class="h-10 w-auto rounded">
                    </a>
                    <!-- Mobile close button -->
                    <button @click="mobileMenuOpen = false" class="md:hidden text-white hover:text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <nav class="flex-1 px-2 space-y-4 overflow-y-auto" x-on:click="if (window.innerWidth < 768 && $event.target.closest('a')) mobileMenuOpen = false">
                    <!-- Main -->
                    <div>
                        <div class="px-3 mb-2">
                            <p class="text-xs font-semibold text-white uppercase tracking-wider">Main</p>
                        </div>
                        <div class="space-y-1">
                            <!-- Dashboard -->
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                        </div>
                    </div>

                    <!-- Operations (copied from Customer_Portal_V2 sidebar) -->
                    <div>
                        <div class="px-3 mt-4 mb-2">
                            <p class="text-xs font-semibold text-white uppercase tracking-wider">Operations</p>
                        </div>
                        <div class="space-y-1">
                            <!-- Users (staff) -->
                            <a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span>Users</span>
                            </a>

                            <!-- Accounts -->
                            <a href="{{ route('accounts.index') }}" class="{{ request()->routeIs('accounts.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <span>Accounts</span>
                            </a>

                            <!-- Products -->
                            <a href="{{ route('location-products.index') }}" class="{{ request()->routeIs('location-products.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span>Products</span>
                            </a>

                            <!-- Stations -->
                            <a href="{{ route('locations.index') }}" class="{{ request()->routeIs('locations.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Stations</span>
                            </a>

                            <!-- Regions -->
                            <a href="{{ route('regions.index') }}" class="{{ request()->routeIs('regions.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Regions</span>
                            </a>

                            <!-- Drivers -->
                            <a href="{{ route('drivers.index') }}" class="{{ request()->routeIs('drivers.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Drivers</span>
                            </a>

                            <!-- Invoices -->
                            <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Invoices</span>
                            </a>

                            <!-- MPE Categories -->
                            <a href="{{ route('mpe-categories.index') }}" class="{{ request()->routeIs('mpe-categories.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"/>
                                </svg>
                                <span>MPE Categories</span>
                            </a>
                        </div>
                    </div>

                    <!-- Reports -->
                    <div x-data="{ reportsOpen: true }">
                        <button @click="reportsOpen = !reportsOpen" class="w-full px-3 mt-4 mb-2 flex items-center justify-between hover:bg-white/5 rounded-lg transition-colors py-2 group">
                            <p class="text-xs font-semibold text-white uppercase tracking-wider">Reports</p>
                            <svg :class="{ 'rotate-180': reportsOpen }" class="h-4 w-4 text-white transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </button>
                        <div x-show="reportsOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="space-y-1">
                            <a href="{{ route('consumption-summary.index') }}" class="{{ request()->routeIs('consumption-summary.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Consumption Summary</span>
                            </a>
                            <a href="{{ route('consumption-report.index') }}" class="{{ request()->routeIs('consumption-report.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>Consumption Report</span>
                            </a>
                            <a href="{{ route('customer-accounts.index') }}" class="{{ request()->routeIs('customer-accounts.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Customer Accounts</span>
                            </a>
                            <a href="{{ route('customer-account-limit-balances.index') }}" class="{{ request()->routeIs('customer-account-limit-balances.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Account Limit Balances</span>
                            </a>
                            <a href="{{ route('customer-sales-entries.index') }}" class="{{ request()->routeIs('customer-sales-entries.*') ? 'bg-white/10 text-white' : 'text-white hover:bg-white/5' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-3 h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span>Sales Entries</span>
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- Sidebar Profile -->
                <div class="px-4 pb-4 mt-4 border-t border-white/10">
                    <p class="text-xs font-semibold text-white uppercase tracking-wider mb-3">Profile</p>
                    <a href="{{ route('profile') }}" class="flex items-center gap-3 group">
                        <div class="h-9 w-9 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-colors">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white truncate group-hover:underline">
                                {{ Auth::user()->name ?? session('fullName') }}
                            </p>
                            <p class="text-xs text-indigo-100/80 truncate">
                                @if(session('customerNumber'))
                                    Acc: {{ session('customerNumber') }}
                                @else
                                    Stabex Customer
                                @endif
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-0 min-w-0 overflow-hidden">
        <!-- Top Header -->
        <header class="bg-white shadow-sm flex-shrink-0 z-10">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-4">
                        <!-- Mobile hamburger menu button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">{{ $header }}</h1>
                    </div>
                    
                    <div class="flex items-center gap-2 sm:gap-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            @php
                                $picture = session('picture');
                            @endphp
                            @if($picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($picture))
                                <img src="{{ asset('storage/' . $picture) }}" alt="{{ session('fullName') }}" class="h-8 w-8 sm:h-10 sm:w-10 rounded-full object-cover border-2 border-gray-300">
                            @else
                                <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-300">
                                    <svg class="h-5 w-5 sm:h-6 sm:w-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="text-right hidden sm:block">
                                <div class="text-sm font-medium text-gray-900">{{ session('fullName') }}</div>
                                <div class="text-xs text-gray-500">{{ session('customerNumber') ? session('customerNumber') . ' · Stabex' : 'Stabex Customer Portal' }}</div>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-2 sm:px-4 py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <svg class="mr-1 sm:mr-2 h-3 w-3 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 border border-red-200">
                    {{ session('error') }}
                </div>
            @endif
            {{ $slot }}
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 flex-shrink-0 mt-auto">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="text-center space-y-1">
                    <p class="text-xs text-gray-600">© Copyright {{ date('Y') }} Stabex. All Rights Reserved</p>
                    <p class="text-xs text-gray-500">Stabex Customer Portal</p>
                </div>
            </div>
        </footer>
    </div>
</div>

@push('styles')
<style>
    @media (min-width: 768px) {
        .flex > aside {
            position: relative !important;
            top: auto !important;
            bottom: auto !important;
            left: auto !important;
            right: auto !important;
        }
    }
</style>
@endpush
