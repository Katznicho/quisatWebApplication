<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles        

        <script>
            if (localStorage.getItem('dark-mode') === 'false' || !('dark-mode' in localStorage)) {
                document.querySelector('html').classList.remove('dark');
                document.querySelector('html').style.colorScheme = 'light';
            } else {
                document.querySelector('html').classList.add('dark');
                document.querySelector('html').style.colorScheme = 'dark';
            }
        </script>         
    </head>
    <body class="font-inter antialiased bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400">

        <main class="flex min-h-screen">

            <!-- Banner Image Section - 50% width -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
                <img src="{{ asset('images/banner.jpeg') }}" alt="Banner" class="w-full h-full object-cover">
            </div>

            <!-- Content Section - 50% width -->
            <div class="w-full lg:w-1/2 flex flex-col bg-white dark:bg-gray-900">

                <!-- Header -->
                <div class="flex items-center justify-between h-20 px-6 lg:px-12 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900">
                    <!-- Logo -->
                    <a class="block" href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/logo.png') }}"
                             alt="Logo"
                             class="h-10 max-h-12 w-auto object-contain"
                             style="max-width: 150px;" />
                    </a>
                </div>

                <!-- Register Form Content -->
                <div class="flex-1 overflow-y-auto">
                    <div class="w-full max-w-4xl mx-auto px-6 lg:px-12 py-8">
                        {{ $slot }}
                    </div>
                </div>

            </div>

        </main>   

        @livewireScriptConfig
    </body>

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
