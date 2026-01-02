<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>Login – Quisat</title>
    <meta name="description" content="Secure login to Quisat – your trusted platform.">
    <meta name="author" content="Quisat Technologies Ltd">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="font-inter antialiased bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400">

    <main class="min-h-screen flex">
        <!-- Banner Image Section - 50% width -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <img src="{{ asset('images/banner.jpeg') }}" alt="Banner" class="w-full h-full object-cover">
        </div>

        <!-- Login Form Section - 50% width -->
        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white dark:bg-gray-900 px-6 py-12 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="Quisat Logo" class="mx-auto h-16 mb-6">
                    </a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 p-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </main>

    @livewireScripts
    @livewireScriptConfig
</body>

</html>
