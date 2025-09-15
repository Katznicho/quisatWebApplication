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

<body class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">

    <main class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white dark:bg-gray-900 p-8 rounded-lg shadow-lg">
            <div class="mb-6 text-center">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="Quisat Logo" class="mx-auto h-24">
                </a>
            </div>

            {{ $slot }}
        </div>
    </main>

    @livewireScripts
    @livewireScriptConfig
</body>

</html>
