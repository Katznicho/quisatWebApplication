<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-gray-900 bg-opacity-30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak
    ></div>

    <!-- Sidebar -->
    <div
        id="sidebar"
        class="flex lg:!flex flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:!w-64 shrink-0 bg-white dark:bg-gray-800 p-4 transition-all duration-200 ease-in-out {{ $variant === 'v2' ? 'border-r border-gray-200 dark:border-gray-700/60' : 'rounded-r-2xl shadow-sm' }}"
        :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-64'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
    >

        <!-- Sidebar header -->
        <div class="flex justify-between mb-10 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-gray-500 hover:text-gray-400" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <span class="sr-only">Close sidebar</span>
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>
            <!-- Logo -->
            <a class="block" href="{{ route('dashboard') }}">
                <svg class="fill-violet-500" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
                    <path d="M31.956 14.8C31.372 6.92 25.08.628 17.2.044V5.76a9.04 9.04 0 0 0 9.04 9.04h5.716ZM14.8 26.24v5.716C6.92 31.372.63 25.08.044 17.2H5.76a9.04 9.04 0 0 1 9.04 9.04Zm11.44-9.04h5.716c-.584 7.88-6.876 14.172-14.756 14.756V26.24a9.04 9.04 0 0 1 9.04-9.04ZM.044 14.8C.63 6.92 6.92.628 14.8.044V5.76a9.04 9.04 0 0 1-9.04 9.04H.044Z" />
                </svg>                
            </a>
        </div>

        <!-- Links -->
        <div class="space-y-8">
            <!-- Pages group -->
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Pages</span>
                </h3>
                <ul class="mt-3">
                    <!-- Dashboard -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['dashboard'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['dashboard']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['dashboard'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M5.936.278A7.983 7.983 0 0 1 8 0a8 8 0 1 1-8 8c0-.722.104-1.413.278-2.064a1 1 0 1 1 1.932.516A5.99 5.99 0 0 0 2 8a6 6 0 1 0 6-6c-.53 0-1.045.076-1.548.21A1 1 0 1 1 5.936.278Z" />
                                        <path d="M6.068 7.482A2.003 2.003 0 0 0 8 10a2 2 0 1 0-.518-3.932L3.707 2.293a1 1 0 0 0-1.414 1.414l3.775 3.775Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Dashboard</span>
                                </div>
                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['dashboard'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['dashboard'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('dashboard')){{ '!text-violet-500' }}@endif" href="{{ route('dashboard') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Main</span>
                                    </a>
                                </li>
                                {{-- <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('analytics')){{ '!text-violet-500' }}@endif" href="{{ route('analytics') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Analytics</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('fintech')){{ '!text-violet-500' }}@endif" href="{{ route('fintech') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Fintech</span>
                                    </a>
                                </li> --}}
                            </ul>
                        </div>
                    </li>
                     <!-- Finance -->
                     <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['finance'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['finance']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['finance'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['finance'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M6 0a6 6 0 0 0-6 6c0 1.077.304 2.062.78 2.912a1 1 0 1 0 1.745-.976A3.945 3.945 0 0 1 2 6a4 4 0 0 1 4-4c.693 0 1.344.194 1.936.525A1 1 0 1 0 8.912.779 5.944 5.944 0 0 0 6 0Z" />
                                        <path d="M10 4a6 6 0 1 0 0 12 6 6 0 0 0 0-12Zm-4 6a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Accounts</span>
                                </div>
                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['finance'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['accounts'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('accounts.index')){{ '!text-violet-500' }}@endif" href="{{ route('accounts.index') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Personal Accounts</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('transactions')){{ '!text-violet-500' }}@endif" href="{{ route('transactions') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Group Accounts</span>
                                    </a>
                                </li>
                                {{-- <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('transaction-details')){{ '!text-violet-500' }}@endif" href="{{ route('transaction-details') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Transaction Details</span>
                                    </a>
                                </li> --}}
                            </ul>
                        </div>
                    </li>


                    <!-- Messages -->
                    {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['messages'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['messages'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('messages') }}">
                            <div class="flex items-center justify-between">
                                <div class="grow flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['messages'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M13.95.879a3 3 0 0 0-4.243 0L1.293 9.293a1 1 0 0 0-.274.51l-1 5a1 1 0 0 0 1.177 1.177l5-1a1 1 0 0 0 .511-.273l8.414-8.414a3 3 0 0 0 0-4.242L13.95.879ZM11.12 2.293a1 1 0 0 1 1.414 0l1.172 1.172a1 1 0 0 1 0 1.414l-8.2 8.2-3.232.646.646-3.232 8.2-8.2Z" />
                                        <path d="M10 14a1 1 0 1 0 0 2h5a1 1 0 1 0 0-2h-5Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Messages</span>
                                </div>
                                <!-- Badge -->
                                <div class="flex flex-shrink-0 ml-2">
                                    <span class="inline-flex items-center justify-center h-5 text-xs font-medium text-white bg-violet-400 px-2 rounded">4</span>
                                </div>
                            </div>
                        </a>
                    </li> --}}


                     <!-- Tasks -->
                     {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['tasks'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['tasks']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['tasks'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['tasks'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M7.586 9H1a1 1 0 1 1 0-2h6.586L6.293 5.707a1 1 0 0 1 1.414-1.414l3 3a1 1 0 0 1 0 1.414l-3 3a1 1 0 1 1-1.414-1.414L7.586 9ZM3.075 4.572a1 1 0 1 1-1.64-1.144 8 8 0 1 1 0 9.144 1 1 0 0 1 1.64-1.144 6 6 0 1 0 0-6.856Z" />
                                    </svg>                                       
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Tasks</span>
                                </div>
                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['tasks'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['tasks'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('tasks-kanban')){{ '!text-violet-500' }}@endif" href="{{ route('tasks-kanban') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Kanban</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('tasks-list')){{ '!text-violet-500' }}@endif" href="{{ route('tasks-list') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">List</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                     --}}
                    <!-- Inbox -->
                    {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['inbox'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['inbox'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('inbox') }}">
                            <div class="flex items-center">
                                <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['inbox'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                    <path d="M11.92 6.851c.044-.027.09-.05.137-.07.481-.275.758-.68.908-1.256.126-.55.169-.81.357-2.058.075-.498.144-.91.217-1.264-4.122.75-7.087 2.984-9.12 6.284a18.087 18.087 0 0 0-1.985 4.585 17.07 17.07 0 0 0-.354 1.506c-.05.265-.076.448-.086.535a1 1 0 0 1-1.988-.226c.056-.49.209-1.312.502-2.357a20.063 20.063 0 0 1 2.208-5.09C5.31 3.226 9.306.494 14.913.004a1 1 0 0 1 .954 1.494c-.237.414-.375.993-.567 2.267-.197 1.306-.244 1.586-.392 2.235-.285 1.094-.789 1.853-1.552 2.363-.748 3.816-3.976 5.06-8.515 4.326a1 1 0 0 1 .318-1.974c2.954.477 4.918.025 5.808-1.556-.628.085-1.335.121-2.127.121a1 1 0 1 1 0-2c1.458 0 2.434-.116 3.08-.429Z" />
                                </svg>   
                                <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Inbox</span>
                            </div>
                        </a>
                    </li> --}}
                    <!-- Calendar -->
                    {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['calendar'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['calendar'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('calendar') }}">
                            <div class="flex items-center">
                                <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['calendar'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                    <path d="M5 4a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2H5Z" />
                                    <path d="M4 0a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V4a4 4 0 0 0-4-4H4ZM2 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4Z" />
                                </svg>
                                <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Calendar</span>
                            </div>
                        </a>
                    </li> --}}
                    <!-- Campaigns -->
                    {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['campaigns'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['campaigns'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('campaigns') }}">
                            <div class="flex items-center">
                                <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['campaigns'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                    <path d="M6.649 1.018a1 1 0 0 1 .793 1.171L6.997 4.5h3.464l.517-2.689a1 1 0 1 1 1.964.378L12.498 4.5h2.422a1 1 0 0 1 0 2h-2.807l-.77 4h2.117a1 1 0 1 1 0 2h-2.501l-.517 2.689a1 1 0 1 1-1.964-.378l.444-2.311H5.46l-.517 2.689a1 1 0 1 1-1.964-.378l.444-2.311H1a1 1 0 1 1 0-2h2.807l.77-4H2.46a1 1 0 0 1 0-2h2.5l.518-2.689a1 1 0 0 1 1.17-.793ZM9.307 10.5l.77-4H6.612l-.77 4h3.464Z" />
                                </svg>
                                <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Campaigns</span>
                            </div>
                        </a>
                    </li> --}}
                    <!-- Settings -->
                    {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['settings'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['settings']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['settings'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['settings'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M10.5 1a3.502 3.502 0 0 1 3.355 2.5H15a1 1 0 1 1 0 2h-1.145a3.502 3.502 0 0 1-6.71 0H1a1 1 0 0 1 0-2h6.145A3.502 3.502 0 0 1 10.5 1ZM9 4.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM5.5 9a3.502 3.502 0 0 1 3.355 2.5H15a1 1 0 1 1 0 2H8.855a3.502 3.502 0 0 1-6.71 0H1a1 1 0 1 1 0-2h1.145A3.502 3.502 0 0 1 5.5 9ZM4 12.5a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0Z" fill-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Settings</span>
                                </div>
                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['settings'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['settings'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('account')){{ '!text-violet-500' }}@endif" href="{{ route('account') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">My Account</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('notifications')){{ '!text-violet-500' }}@endif" href="{{ route('notifications') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">My Notifications</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('apps')){{ '!text-violet-500' }}@endif" href="{{ route('apps') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Connected Apps</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('plans')){{ '!text-violet-500' }}@endif" href="{{ route('plans') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Plans</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('billing')){{ '!text-violet-500' }}@endif" href="{{ route('billing') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Billing & Invoices</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('feedback')){{ '!text-violet-500' }}@endif" href="{{ route('feedback') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Give Feedback</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li> --}}
                  
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