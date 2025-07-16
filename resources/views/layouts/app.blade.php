<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <title>{{ env('APP_NAME') }} – Smart Payments and Collections Platform</title>
    <meta name="title" content="MarzPay – Smart Payments and Collections Platform">
    <meta name="description"
        content="MarzPay is a powerful platform for managing digital transactions, collections, and payouts with ease. Trusted by businesses across Africa.">
    <meta name="keywords"
        content="MarzPay, payments, digital wallet, collections, payouts, mobile money, financial platform, business payments, bulk payments, Uganda fintech">
    <meta name="author" content="Marz Innovations Ltd">
    <meta name="robots" content="index, follow">
    <meta name="language" content="en">
    <meta name="theme-color" content="#011478" />
    <meta property="og:title" content="MarzPay – Smart Payments and Collections Platform" />
    <meta property="og:description"
        content="MarzPay enables businesses to send and receive payments securely through mobile money and bank integrations." />
    <meta property="og:image" content="{{ asset('images/logo.png') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="MarzPay – Smart Payments and Collections Platform">
    <meta name="twitter:description"
        content="MarzPay simplifies business payments and collections for growing organizations.">
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
    :class="{ 'sidebar-expanded': sidebarExpanded }" x-data="{ sidebarOpen: false, sidebarExpanded: localStorage.getItem('sidebar-expanded') == 'true' }" x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value))">

    <script>
        if (localStorage.getItem('sidebar-expanded') == 'true') {
            document.querySelector('body').classList.add('sidebar-expanded');
        } else {
            document.querySelector('body').classList.remove('sidebar-expanded');
        }
    </script>

    <!-- Page wrapper -->
    <div class="flex h-[100dvh] overflow-hidden">

        <x-app.sidebar :variant="$attributes['sidebarVariant']" />

        <!-- Content area -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden @if ($attributes['background']) {{ $attributes['background'] }} @endif"
            x-ref="contentarea">

            <x-app.header :variant="$attributes['headerVariant']" />

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
    {{-- Success Message --}}
    @if (Session::has('success'))
        Swal.fire({
            icon: 'success',
            title: 'Done',
            text: '{{ Session::get('success') }}',
            confirmButtonColor: "#3a57e8"
        });
    @endif
    {{-- Errors Message --}}
    @if (Session::has('error'))
        Swal.fire({
            icon: 'error',
            title: 'Opps!!!',
            text: '{{ Session::get('error') }}',
            confirmButtonColor: "#3a57e8"
        });
    @endif
    @if (Session::has('errors') || (isset($errors) && is_array($errors) && $errors->any()))
        Swal.fire({
            icon: 'error',
            title: 'Opps!!!',
            text: '{{ Session::get('errors')->first() }}',
            confirmButtonColor: "#3a57e8"
        });
    @endif
</script>

</html>
