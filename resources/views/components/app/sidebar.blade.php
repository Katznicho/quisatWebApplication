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

        {{-- sidebar header --}}

        <!-- Links -->
        <div class="space-y-8">
            <!-- Pages group -->
            <div>

                <ul class="mt-3 space-y-2" x-data="{ openGroup: '' }">

                    <!-- Dashboard (Available for all businesses) -->
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center pl-4 pr-3 py-2 rounded-lg bg-blue-100 text-blue-900 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span class="ml-3">{{ auth()->user()->business_id == 1 ? 'Dashboard' : 'My Dashboard' }}</span>
                        </a>
                    </li>

                    <!-- Chat & Communications -->
                    @if (auth()->user()->business_id != 1 && $business->hasFeatureByName('Chat & Communication'))
                    <li>
                        <a href="{{ route('chat.index') }}"
                            class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                            <span class="ml-3">Chat & Communications</span>
                        </a>
                    </li>
                    @endif

                    <!-- Business Advertising -->
                    @if (auth()->user()->business_id != 1 && $business->hasFeatureByName('Business Advertising'))
                    <li>
                        <a href="{{ route('advertisements.index') }}"
                            class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l-1-3m1 3l-1-3m-16.5-3h9v1.5m-9 0h3m-3 0l-1 3m4-3l1 3m0 0l-1-3m1 3l-1-3" />
                            </svg>
                            <span class="ml-3">Business Advertising</span>
                        </a>
                    </li>
                    @endif

                    <!-- Kids Events -->
                    @if (auth()->user()->business_id != 1 && $business->hasFeatureByName('Kids Events Management'))
                    <li>
                        <a href="{{ route('kids-events.index') }}"
                            class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            <span class="ml-3">Kids Events</span>
                        </a>
                    </li>
                    @endif

                    <!-- Staff -->
                    @if (auth()->user()->business_id == 1 || $business->hasFeatureByName('Staff Management'))
                    <li>
                        <button @click="openGroup === 'staff' ? openGroup = '' : openGroup = 'staff'"
                            :class="openGroup === 'staff' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                <span class="ml-3">{{ auth()->user()->business_id == 1 ? 'Staff' : 'My Staff' }}</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'staff' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'staff'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('users.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">
                                    {{ auth()->user()->business_id == 1 ? 'Manage Staff' : 'View My Staff' }}
                                </a>
                            </li>
                            @if (auth()->user()->business_id == 1 || $business->hasFeatureByName('Role-Based Access Control'))
                            <li><a href="{{ route('roles.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">
                                    {{ auth()->user()->business_id == 1 ? 'Manage Roles' : 'My Roles' }}
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <!-- Admin Management (Only for System Admins) -->
                    @if (auth()->user()->isAdmin())
                        <li>
                            <button @click="openGroup === 'admin' ? openGroup = '' : openGroup = 'admin'"
                                :class="openGroup === 'admin' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                    'text-gray-700 hover:text-blue-700'"
                                class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                    <span class="ml-3">Admin Management</span>
                                </div>
                                <svg class="w-4 h-4 transform transition-transform duration-200"
                                    :class="{ 'rotate-180': openGroup === 'admin' }" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <ul x-show="openGroup === 'admin'" x-collapse class="mt-1 space-y-1 pl-10">
                                <li><a href="{{ route('admin.dashboard') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Admin
                                        Dashboard</a></li>
                                <li><a href="{{ route('admin.create-admin') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Create Admin</a>
                                </li>
                                <li><a href="{{ route('admin.create-staff') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Create Staff</a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- School Features (Hidden for business_id == 1) -->
                    @if (auth()->user()->business_id != 1)
                        <!-- Terms -->
                        @if ($business->hasFeatureByName('Term Management'))
                        <li>
                            <a href="{{ route('school-management.terms') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                <span class="ml-3">Terms</span>
                            </a>
                        </li>
                        @endif

                        <!-- Classes -->
                        @if ($business->hasFeatureByName('Class Room Management'))
                        <li>
                            <a href="{{ route('school-management.classrooms') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                                </svg>
                                <span class="ml-3">Classes</span>
                            </a>
                        </li>
                        @endif

                        <!-- Subjects -->
                        @if ($business->hasFeatureByName('Subject Management'))
                        <li>
                            <a href="{{ route('school-management.subjects') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                                <span class="ml-3">Subjects</span>
                            </a>
                        </li>
                        @endif

                        <!-- Students -->
                        @if ($business->hasFeatureByName('Student Management'))
                        <li>
                            <a href="{{ route('school-management.students') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <span class="ml-3">Students</span>
                            </a>
                        </li>
                        @endif

                        <!-- Attendance Tracking -->
                        @if ($business->hasFeatureByName('Attendance Management'))
                        <li>
                            <a href="{{ route('school-management.attendance') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="ml-3">Attendance Tracking</span>
                            </a>
                        </li>
                        @endif

                        <!-- Calendar & Events -->
                        @if ($business->hasFeatureByName('Calendar & Events Management'))
                        <li>
                            <a href="{{ route('school-management.calendar-events') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9 0h9" />
                                </svg>
                                <span class="ml-3">Calendar & Events</span>
                            </a>
                        </li>
                        @endif

                        <!-- Timetable -->
                        @if ($business->hasFeatureByName('Timetable Management'))
                        <li>
                            <a href="{{ route('school-management.timetable') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="ml-3">Timetable</span>
                            </a>
                        </li>
                        @endif

                        <!-- Assignments & Grades -->
                        @if ($business->hasFeatureByName('Grade Management'))
                        <li>
                            <a href="{{ route('school-management.grades') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                                </svg>
                                <span class="ml-3">Assignments & Grades</span>
                            </a>
                        </li>
                        @endif

                        <!-- Exams -->
                        @if ($business->hasFeatureByName('Exam Management'))
                        <li>
                            <a href="{{ route('school-management.exams') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                                <span class="ml-3">Exams</span>
                            </a>
                        </li>
                        @endif

                        <!-- Parents & Guardians -->
                        @if ($business->hasFeatureByName('Parent Guardian Management'))
                        <li>
                            <a href="{{ route('school-management.parents') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <span class="ml-3">Parents & Guardians</span>
                            </a>
                        </li>
                        @endif

                        <!-- Financials -->
                        @if ($business->hasFeatureByName('Fee Management'))
                        <li>
                            <a href="{{ route('school-management.fees') }}"
                                class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                <span class="ml-3">Financials</span>
                            </a>
                        </li>
                        @endif
                    @endif

                    <!-- Christian Kids Hub (Only for business_id == 1) -->
                    @if (auth()->user()->business_id == 1)
                        <li>
                            <button @click="openGroup === 'programs' ? openGroup = '' : openGroup = 'programs'"
                                :class="openGroup === 'programs' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                    'text-gray-700 hover:text-blue-700'"
                                class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="ml-3">Christian Kids Hub</span>
                                </div>
                                <svg class="w-4 h-4 transform transition-transform duration-200"
                                    :class="{ 'rotate-180': openGroup === 'programs' }" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <ul x-show="openGroup === 'programs'" x-collapse class="mt-1 space-y-1 pl-10">
                                <li><a href="{{ route('programs.index') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Programs</a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- Kids Mart -->
                    @if (auth()->user()->business_id != 1 && $business->hasFeatureByName('Kids Mart'))
                    <li>
                        <a href="{{ route('products.index') }}"
                            class="flex items-center pl-4 pr-3 py-2 rounded-md text-gray-700 hover:text-blue-700 hover:bg-blue-50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            <span class="ml-3">Kids Mart</span>
                        </a>
                    </li>
                    @endif

                    <!-- Payments -->
                    <li>
                        <button @click="openGroup === 'payments' ? openGroup = '' : openGroup = 'payments'"
                            :class="openGroup === 'payments' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                <span class="ml-3">Payments</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'payments' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'payments'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('payments.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">All Payments</a>
                            </li>
                            <li><a href="{{ route('payments.pending') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Pending Payments</a>
                            </li>
                            <li><a href="{{ route('payments.reports') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Payment Reports</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Businesses -->
                    <li>
                        <button @click="openGroup === 'business' ? openGroup = '' : openGroup = 'business'"
                            :class="openGroup === 'business' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                'text-gray-700 hover:text-blue-700'"
                            class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                                <span class="ml-3">{{ auth()->user()->business_id == 1 ? 'Businesses' : 'My Business' }}</span>
                            </div>
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': openGroup === 'business' }" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="openGroup === 'business'" x-collapse class="mt-1 space-y-1 pl-10">
                            <li><a href="{{ route('businesses.index') }}"
                                    class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">
                                    {{ auth()->user()->business_id == 1 ? 'Manage Business' : 'View My Business' }}
                                </a>
                            </li>
                        </ul>
                    </li>




                    <!-- Settings (Only for business_id == 1) -->
                    @if (auth()->user()->business_id == 1)
                        <li>
                            <button @click="openGroup === 'settings' ? openGroup = '' : openGroup = 'settings'"
                                :class="openGroup === 'settings' ? 'border border-blue-500 text-blue-700 bg-blue-50' :
                                    'text-gray-700 hover:text-blue-700'"
                                class="flex items-center justify-between w-full text-left pl-4 pr-3 py-2 rounded-md">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658a2.745 2.745 0 01-2.275-1.203M10.53 1.527a2.745 2.745 0 00-2.275 1.204m.569 19.374a2.745 2.745 0 002.275 1.203m.569-19.374a2.745 2.745 0 012.275-1.203m-3.637 19.658l-.75-1.3m7.5-12.99l-.75-1.3m-6.063 16.658l-.75 1.3m7.5-12.99l-.75 1.3" />
                                    </svg>
                                    <span class="ml-3">Settings</span>
                                </div>
                                <svg class="w-4 h-4 transform transition-transform duration-200"
                                    :class="{ 'rotate-180': openGroup === 'settings' }" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <ul x-show="openGroup === 'settings'" x-collapse class="mt-1 space-y-1 pl-10">
                                <li><a href="{{ route('business-categories.index') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Business
                                        Categories</a>
                                </li>
                                <li><a href="{{ route('features.index') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Features</a>
                                </li>
                                <li><a href="{{ route('currency.index') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage
                                        Currencies</a>
                                </li>
                                <li><a href="{{ route('roles.index') }}"
                                        class="block text-sm text-gray-700 hover:text-blue-700 py-1.5">Manage Roles</a>
                                </li>
                            </ul>
                        </li>
                    @endif

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
