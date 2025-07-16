<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>Login – MarzPay</title>
    <meta name="description" content="Secure login to MarzPay – your trusted payment management platform.">
    <meta name="author" content="Marz Innovations Ltd">

    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">

    <main class="min-h-screen flex">
        <!-- Left: Form -->
        <div class="w-full md:w-1/2 flex flex-col justify-center px-6 lg:px-24 py-12 bg-white dark:bg-gray-900">
            <div class="mb-8">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/kashtre_logo.svg') }}" alt="kashtre Logo">
                </a>
            </div>

            <div class="w-full max-w-md mx-auto">
                {{ $slot }}
            </div>
        </div>

        <!-- Right: Illustration -->
        <div class="hidden md:flex md:w-1/2 bg-gray-50 dark:bg-gray-800 items-center justify-center">
            <img src="{{ asset('images/auth.jpeg') }}" alt="Login Illustration" class="w-full h-full object-cover" />
        </div>
    </main>

    @livewireScripts
    @livewireScriptConfig
</body>

</html>
