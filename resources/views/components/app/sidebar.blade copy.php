<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'" aria-hidden="true" x-cloak></div>

    <!-- Sidebar -->
    <div id="sidebar"
        class="flex lg:!flex flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 shrink-0 bg-white dark:bg-gray-800 p-4 transition-all duration-200 ease-in-out {{ $variant === 'v2' ? 'border-r border-gray-200 dark:border-gray-700/60' : 'rounded-r-2xl shadow-sm' }}"
        x-data="{ sidebarOpen: true, open: true }"
        :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-64'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false">

        <!-- Sidebar header -->
        <div class="flex justify-between mb-6 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-gray-500 hover:text-gray-400" @click.stop="sidebarOpen = !sidebarOpen"
                aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <span class="sr-only">Close sidebar</span>
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>
            <!-- Logo -->
            <div class="flex flex-col items-center">
                <img src="{{ asset('images/logo.png') }}" alt="logo" width="100" height="100">
                <h1 class="text-[#011478] font-bold text-lg mt-2">NextTraderGenAl</h1>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="space-y-2">
            <!-- UI Area -->
            <ul class="mt-3">
                <!-- Dashboard -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['dashboard'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('dashboard')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('dashboard') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Dashboard</span>
                        </div>
                    </a>
                </li>

                <!-- Payments -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['payments'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('payments.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('payments.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2  viele 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Payments</span>
                        </div>
                    </a>
                </li>

                <!-- Subscriptions -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['subscriptions'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('subscriptions.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('subscriptions.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Subscriptions</span>
                        </div>
                    </a>
                </li>

                <!-- Trading History -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['history'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('history.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('history.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Trading History</span>
                        </div>
                    </a>
                </li>

                <!-- Bot Configuration -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['bot-configuration'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('bot-configuration.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('bot-configuration.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Bot Configuration</span>
                        </div>
                    </a>
                </li>

                <!-- Charts -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['charts'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('charts.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('charts.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Charts</span>
                        </div>
                    </a>
                </li>

                <!-- Signals -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['signals'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('signals.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('signals.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Signals</span>
                        </div>
                    </a>
                </li>

                <!-- Addons -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['addons'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('addons.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('addons.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 011-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Addons</span>
                        </div>
                    </a>
                </li>

                <!-- Reports -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['reports'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('reports.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('reports.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Reports</span>
                        </div>
                    </a>
                </li>

                <!-- Support -->
                <li
                    class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if (in_array(Request::segment(1), ['support'])) {{ 'from-[#011478]/[0.12] dark:from-[#011478]/[0.24] to-[#011478]/[0.04]' }} @endif">
                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-[#d1111b] transition truncate @if (Route::is('support.*')) {{ '!text-[#011478]' }} @endif"
                        href="{{ route('support.index') }}">
                        <div class="flex items-center">
                            <svg class="shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="text-sm font-medium ml-3">Support</span>
                        </div>
                    </a>
                </li>
            </ul>
            <!-- UI Area -->
        </div>

        <!-- Rest of the sidebar code remains the same -->
    </div>
</div>