@props([
    'sidebarVariant' => 'default',
    'headerVariant' => 'default',
    'background' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ env('APP_NAME') }} – Admin Portal</title>
    <meta name="title" content="{{ env('APP_NAME') }} – Admin Portal">
    <meta name="description"
        content="{{ env('APP_NAME') }} is a comprehensive school and business management platform. Manage your institution with ease.">
    <meta name="keywords"
        content="{{ env('APP_NAME') }}, school management, business management, admin portal, education platform, Uganda">
    <meta name="author" content="{{ env('APP_COMPANY_NAME', 'Quisat Technologies Ltd') }}">
    <meta name="robots" content="index, follow">
    <meta name="language" content="en">
    <meta name="theme-color" content="#011478" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <meta property="og:title" content="{{ env('APP_NAME') }} – Admin Portal" />
    <meta property="og:description"
        content="{{ env('APP_NAME') }} provides comprehensive management tools for schools and businesses." />
    <meta property="og:image" content="{{ asset('images/logo.png') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ env('APP_NAME') }} – Admin Portal">
    <meta name="twitter:description"
        content="{{ env('APP_NAME') }} simplifies school and business management for growing organizations.">
    <meta name="twitter:image" content="{{ asset('images/logo.png') }}">


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @filamentStyles
    <!-- Styles -->
    @livewireStyles


</head>

<body class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400"
    :class="{ 'sidebar-expanded': sidebarExpanded }" x-data="{ sidebarOpen: window.innerWidth >= 1024, sidebarExpanded: true }" x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value))">

    <script>
        if (localStorage.getItem('sidebar-expanded') == 'true') {
            document.querySelector('body').classList.add('sidebar-expanded');
        } else {
            document.querySelector('body').classList.remove('sidebar-expanded');
        }
    </script>

    <!-- Page wrapper -->
    <div class="flex h-[100dvh] overflow-hidden">

        <x-app.sidebar :variant="$sidebarVariant" />

        <!-- Content area -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden @if ($background) {{ $background }} @endif"
            x-ref="contentarea">

            <x-app.header :variant="$headerVariant" />

            <main class="grow">
                {{ $slot }}
            </main>
            <!-- Footer -->
            <footer class="w-full bg-gray-100 text-gray-600 py-2 border-t border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <p class="text-sm text-gray-500">© Copyright {{ date('Y') }} {{ env('APP_NAME') }}. All Rights Reserved</p>
                    <p class="text-sm text-gray-500">{{ env('APP_NAME') }} is a product of {{ env('APP_COMPANY_NAME') }}</p>
                </div>
            </footer>

        </div>

    </div>
    @livewire('notifications')
    @filamentScripts
     @livewireScripts
    @livewireScriptConfig
</body>

<div class="w-full bg-black text-white text-sm overflow-hidden fixed top-0 z-50">

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    window.addEventListener('load', function() {
        // Initialize any global JavaScript here
    });
</script>

</html>


