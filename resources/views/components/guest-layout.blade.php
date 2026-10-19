<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name', 'GreensWell') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/stabex_logo_big.jpg') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="antialiased text-gray-600 bg-gray-100 font-inter dark:bg-gray-900 dark:text-gray-400">
    <main class="bg-white dark:bg-gray-900">
        <div class="relative flex">
            <!-- Left content: logo + form -->
            <div class="w-full md:w-1/2">
                <div class="min-h-[100dvh] h-full flex flex-col after:flex-1">
                    <!-- Header / Logo -->
                    <div class="flex-1">
                        <div class="flex items-center justify-center h-16 px-4 sm:px-6 lg:px-8 pt-16 pb-8">
                            <div class="flex flex-col items-center justify-center w-full">
                                <a href="/" class="flex flex-col items-center gap-2 hover:opacity-90 transition-opacity">
                                    <img src="{{ asset('images/stabex_logo_big.jpg') }}" alt="Stabex logo" width="150" height="150" class="mx-auto">
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Auth card -->
                    <div class="w-full max-w-sm px-4 py-8 mx-auto">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <!-- Right image -->
            <div class="absolute top-0 bottom-0 right-0 hidden md:block md:w-1/2" aria-hidden="true">
                <img
                    class="object-cover object-center w-full h-full"
                    src="{{ asset('images/station_customer.jpg') }}"
                    width="760"
                    height="1024"
                    alt="Customer portal background"
                />
            </div>
        </div>
    </main>

    @stack('scripts')
</body>
</html>
