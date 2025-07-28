<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div class="fixed inset-0 bg-gray-900/30 z-40 lg:hidden lg:z-auto transition-opacity duration-200" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'" aria-hidden="true" x-cloak></div>

    <!-- Sidebar -->
    <div id="sidebar" class="flex lg:flex! flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:w-64! shrink-0 bg-white dark:bg-gray-800 p-4 transition-all duration-200 ease-in-out {{ $variant === 'v2' ? 'border-r border-gray-200 dark:border-gray-700/60' : 'rounded-r-2xl shadow-xs' }}" :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-64'" @click.outside="sidebarOpen = false" @keydown.escape.window="sidebarOpen = false">

        <!-- Sidebar header -->
        <!-- Sidebar header -->
        <div class="flex justify-between mb-10 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-gray-500 hover:text-gray-400" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <span class="sr-only">Close sidebar</span>
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>

            <!-- Logo and Business Info -->
            <div class="flex flex-col items-center w-full">
                <!-- Top-level System Name -->
                <h1 class="text-[#011478] font-extrabold text-xl mb-1">{{ env('APP_NAME') }}</h1>

                <!-- Business Logo -->
                @php
                $logoPath = $business->logo ?? null;
                @endphp
                <div class="w-16 h-16 rounded-full overflow-hidden border border-gray-300 shadow-sm bg-white">
                    @if ($logoPath && file_exists(public_path('storage/' . $logoPath)))
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="Business Logo" class="w-full h-full object-contain">
                    @else
                    <img src="{{ asset('images/logo.png') }}" alt="Default Logo" class="w-full h-full object-contain">
                    @endif
                </div>

                <!-- Business Name -->
                <h2 class="text-[#011478] font-bold text-sm mt-2 text-center">
                    {{ $business->name ?? 'N/A' }}
                </h2>

                <!-- Account Number -->
                <p class="text-gray-500 text-xs mt-0.5">
                    A/C: {{ $business->account_number ?? 'N/A' }}
                </p>
            </div>
        </div>

        {{-- sidebar header --}}

        <!-- Links -->
        <div class="space-y-8">
            <!-- Pages group -->
            <div>

                <ul class="mt-3 space-y-2" x-data="{ openGroup: '' }">

                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center pl-4 pr-3 py-2 rounded-lg bg-blue-100 text-blue-900 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    <!-- Staff -->
                    <li>
                        <button @click="openGroup === 'staff' ? openGroup = '' : openGroup = 'staff'" :class="openGroup === 'staff' ? 'border border-blue-500 text-blue-700 bg-blue-50' : 'text-gray-700 hover:text-blue-700'" class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                <span class="ml-3">Staff</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': openGroup === 'staff' }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'staff'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('users.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Staff</a></li>
                            
                        </ul>
                    </li>

                    <!-- programs  is like  a dashboard for the programs-->
                    <li>
                        <button @click="openGroup === 'programs' ? openGroup = '' : openGroup = 'programs'" :class="openGroup === 'programs' ? 'border border-blue-500 text-blue-700 bg-blue-50' : 'text-gray-700 hover:text-blue-700'" class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="ml-3">Christian Kids Hub</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': openGroup === 'programs' }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'programs'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('programs.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Programs</a></li>
                        </ul>
                    </li>

                    <!-- Businesses -->
                    <li>
                        <button @click="openGroup === 'business' ? openGroup = '' : openGroup = 'business'" :class="openGroup === 'business' ? 'border border-blue-500 text-blue-700 bg-blue-50' : 'text-gray-700 hover:text-blue-700'" class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                                <span class="ml-3">Businesses</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': openGroup === 'business' }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'business'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('businesses.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Business</a>
                            </li>

                        </ul>
                    </li>




                    {{-- <!-- Settings --}}
                    <li>
                        <button @click="openGroup === 'settings' ? openGroup = '' : openGroup = 'settings'" :class="openGroup === 'settings' ? 'border border-blue-500 text-blue-700 bg-blue-50' : 'text-gray-700 hover:text-blue-700'" class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658a2.745 2.745 0 01-2.275-1.203M10.53 1.527a2.745 2.745 0 00-2.275 1.204m.569 19.374a2.745 2.745 0 002.275 1.203m.569-19.374a2.745 2.745 0 012.275-1.203m-3.637 19.658l-.75-1.3m7.5-12.99l-.75-1.3m-6.063 16.658l-.75 1.3m7.5-12.99l-.75 1.3" />
                                </svg>
                                <span class="ml-3">Settings</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': openGroup === 'settings' }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'settings'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('business-categories.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Business Categories</a>
                            </li>
                            <li><a href="{{ route('features.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Features</a>
                            </li>
                            <li><a href="{{ route('currency.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Currencies</a>
                            </li>
                            <li><a href="{{ route('roles.index') }}" class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Roles</a></li>


                    </li>
                </ul>
                </li>
                {{-- Settings --}}

                </ul>



            </div>
        </div>

        <!-- Expand / collapse button -->
        <div class="pt-3 hidden lg:inline-flex 2xl:hidden justify-end mt-auto">
            <div class="w-12 pl-4 pr-3 py-2">
                <button class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors" @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 sidebar-expanded:rotate-180" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
