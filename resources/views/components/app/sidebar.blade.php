<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div class="fixed inset-0 bg-gray-900/30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'" aria-hidden="true" x-cloak></div>

    <!-- Sidebar -->
    <div id="sidebar"
        class="flex lg:flex! flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:w-64! shrink-0 bg-white dark:bg-gray-800 p-4 transition-all duration-200 ease-in-out {{ $variant === 'v2' ? 'border-r border-gray-200 dark:border-gray-700/60' : 'rounded-r-2xl shadow-xs' }}"
        :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-64'" @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false">

        <!-- Sidebar header -->
        <!-- Sidebar header -->
        <div class="flex justify-between mb-10 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-gray-500 hover:text-gray-400" @click.stop="sidebarOpen = !sidebarOpen"
                aria-controls="sidebar" :aria-expanded="sidebarOpen">
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
                        <img src="{{ asset('storage/' . $logoPath) }}" alt="Business Logo"
                            class="w-full h-full object-contain">
                    @else
                        <img src="{{ asset('images/logo.png') }}" alt="Default Logo"
                            class="w-full h-full object-contain">
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

        {{--  sidebar header --}}

        <!-- Links -->
        <div class="space-y-8">
            <!-- Pages group -->
            <div>
                {{-- <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6"
                        aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Pages</span>
                </h3> --}}
                <ul class="mt-3 space-y-2" x-data="{ openGroup: '' }">

                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center pl-4 pr-3 py-2 rounded-lg bg-blue-100 text-blue-900 font-semibold">
                            🏠 <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    <!-- Staff -->
                    <li>
                        <button @click="openGroup === 'staff' ? openGroup = '' : openGroup = 'staff'"
                            :class="openGroup === 'staff' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>🧑‍💼 <span class="ml-3">Staff</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'staff' }" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'staff'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('users.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Staff</a></li>
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage
                                    Contractors</a></li>
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Contractor
                                    Services</a></li>
                            <li><a href="{{ route('roles.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Roles</a></li>
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Staff
                                    Groups</a></li>
                        </ul>
                    </li>

                    <!-- Businesses -->
                    <li>
                        <button @click="openGroup === 'business' ? openGroup = '' : openGroup = 'business'"
                            :class="openGroup === 'business' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>🏢 <span class="ml-3">Businesses</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'business' }" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'business'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('businesses.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Business</a>
                            </li>
                            <li><a href="{{ route('branches.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Branches</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Manage Finance -->
                    <li>
                        <button @click="openGroup === 'finance' ? openGroup = '' : openGroup = 'finance'"
                            :class="openGroup === 'finance' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>💰 <span class="ml-3">Manage Finance</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'finance' }" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'finance'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Finance Dashboard</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Module Management -->
                    <li>
                        <button @click="openGroup === 'module' ? openGroup = '' : openGroup = 'module'"
                            :class="openGroup === 'module' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>🧩 <span class="ml-3">Module Management</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'module' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'module'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Modules</a></li>
                        </ul>
                    </li>

                    <!-- Clients -->
                    <li>
                        <button @click="openGroup === 'clients' ? openGroup = '' : openGroup = 'clients'"
                            :class="openGroup === 'clients' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>👥 <span class="ml-3">Clients</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'clients' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'clients'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Client
                                    Management</a></li>
                        </ul>
                    </li>

                    <!-- Reports -->
                    <li>
                        <button @click="openGroup === 'reports' ? openGroup = '' : openGroup = 'reports'"
                            :class="openGroup === 'reports' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>📊 <span class="ml-3">Reports</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'reports' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'reports'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('dashboard') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">View Reports</a>
                            </li>
                        </ul>
                    </li>

                    {{-- <!-- Settings --}}
                    <li>
                        <button @click="openGroup === 'settings' ? openGroup = '' : openGroup = 'settings'"
                            :class="openGroup === 'settings' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <span>⚙️ <span class="ml-3">Settings</span></span>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'settings' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'settings'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('service-points.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Service Points</a>
                            </li>
                            <li><a href="{{ route('departments.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Departments</a>
                            </li>
                            <li><a href="{{ route('qualifications.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Qualifications</a>
                            </li>
                            <li><a href="{{ route('titles.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Titles</a>
                            </li>
                            <li><a href="{{ route('rooms.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Rooms</a>
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
                <button
                    class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors"
                    @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 sidebar-expanded:rotate-180"
                        xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path
                            d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
