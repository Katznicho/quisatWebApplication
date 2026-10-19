<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Features - GreensWell | SMS Messaging Platform</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Discover the powerful features of GreensWell - lightning-fast messaging, enterprise security, real-time analytics, and easy API integration.">
    <meta name="keywords" content="SMS features, SMS API, SMS Gateway, Bulk SMS, SMS Service">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto max-w-7xl px-4 sm:px-6 py-4">
            <div class="flex justify-between items-center">
                <a href="{{ route('home') }}" class="flex items-center text-gray-800 no-underline" aria-label="GreensWell Home">
                    <img src="{{ asset('logo.png') }}" alt="GreensWell Logo" class="h-8 sm:h-10 w-auto" width="120" height="40">
                </a>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ route('features') }}" class="text-gray-600 font-medium hover:text-primary-700 transition-colors">Features</a>
                    <a href="{{ route('pricing') }}" class="text-gray-600 font-medium hover:text-primary-700 transition-colors">Pricing</a>
                    <a href="{{ route('docs.index') }}" class="text-gray-600 font-medium hover:text-primary-700 transition-colors">Documentation</a>
                    @guest
                        <a href="{{ route('login') }}" class="px-4 py-2 sm:px-6 sm:py-2.5 border-2 text-white rounded-lg font-semibold transition-all text-sm sm:text-base" style="border-color: #004f78; color: #004f78;">Sign In</a>
                        <a href="{{ route('login') }}" class="px-4 py-2 sm:px-6 sm:py-2.5 text-white rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="background-color: #004f78;">Sign In</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 sm:px-6 sm:py-2.5 text-white rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="background-color: #004f78;">Dashboard</a>
                    @endguest
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="md:hidden p-2 text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4 space-y-3">
                <a href="{{ route('features') }}" class="block text-gray-600 font-medium hover:text-primary-700 transition-colors py-2">Features</a>
                <a href="{{ route('pricing') }}" class="block text-gray-600 font-medium hover:text-primary-700 transition-colors py-2">Pricing</a>
                <a href="{{ route('docs.index') }}" class="block text-gray-600 font-medium hover:text-primary-700 transition-colors py-2">Documentation</a>
                @guest
                    <a href="{{ route('login') }}" class="block px-4 py-2 border-2 rounded-lg font-semibold text-center" style="border-color: #004f78; color: #004f78;">Sign In</a>
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-white rounded-lg font-semibold text-center" style="background-color: #004f78;">Sign In</a>
                @else
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-white rounded-lg font-semibold text-center" style="background-color: #004f78;">Dashboard</a>
                @endguest
            </div>
        </nav>
    </header>

    <!-- Features Section -->
    <section class="py-12 sm:py-16 md:py-20 bg-gray-50">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6">
            <div class="text-center mb-12">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">Why Choose GreensWell?</h1>
                <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto">Everything you need to send SMS messages efficiently and effectively</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 sm:w-15 sm:h-15 rounded-xl flex items-center justify-center mb-4 sm:mb-6 text-white" style="background-color: #004f78;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Lightning Fast</h3>
                    <p class="text-sm sm:text-base text-gray-600">Send thousands of messages in seconds with our optimized infrastructure. No delays, no waiting.</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 sm:w-15 sm:h-15 rounded-xl flex items-center justify-center mb-4 sm:mb-6 text-white" style="background-color: #004f78;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Secure & Reliable</h3>
                    <p class="text-sm sm:text-base text-gray-600">Your data is protected with enterprise-grade security. 99.9% uptime guarantee for peace of mind.</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 sm:w-15 sm:h-15 rounded-xl flex items-center justify-center mb-4 sm:mb-6 text-white" style="background-color: #004f78;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Affordable Pricing</h3>
                    <p class="text-sm sm:text-base text-gray-600">Competitive rates that scale with your business. Pay only for what you use, no hidden fees.</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 sm:w-15 sm:h-15 rounded-xl flex items-center justify-center mb-4 sm:mb-6 text-white" style="background-color: #004f78;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Real-time Analytics</h3>
                    <p class="text-sm sm:text-base text-gray-600">Track delivery status, view detailed reports, and monitor performance metrics in real-time.</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 sm:w-15 sm:h-15 rounded-xl flex items-center justify-center mb-4 sm:mb-6 text-white" style="background-color: #004f78;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Easy Integration</h3>
                    <p class="text-sm sm:text-base text-gray-600">Simple API and webhooks for seamless integration with your existing applications and workflows.</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 sm:w-15 sm:h-15 rounded-xl flex items-center justify-center mb-4 sm:mb-6 text-white" style="background-color: #004f78;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Global Reach</h3>
                    <p class="text-sm sm:text-base text-gray-600">Send SMS messages to any mobile number worldwide. Support for all major carriers and countries.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="text-white py-12 sm:py-16 md:py-20 text-center" style="background-color: #004f78;">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4">Ready to Sign In?</h2>
            <p class="text-base sm:text-lg mb-6 sm:mb-8 opacity-95">Join thousands of businesses already using GreensWell to connect with their customers</p>
            @guest
                <a href="{{ route('login') }}" class="inline-block w-full sm:w-auto px-6 sm:px-8 py-2.5 sm:py-3 bg-white rounded-lg font-semibold hover:shadow-xl hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="color: #004f78;">Sign In</a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-block w-full sm:w-auto px-6 sm:px-8 py-2.5 sm:py-3 bg-white rounded-lg font-semibold hover:shadow-xl hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="color: #004f78;">Go to Dashboard</a>
            @endguest
        </div>
    </section>

    <!-- Footer -->
    @include('components.footer')

    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
            
            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        }
    </script>
</body>
</html>

