<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing - GreensWell | SMS Messaging Platform</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Simple, transparent pricing for GreensWell. Choose a package to get better SMS rates. Packages are free - you only pay for the SMS you send.">
    <meta name="keywords" content="SMS pricing, SMS rates, SMS cost, SMS packages, affordable SMS">
    
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
                        <a href="{{ route('login') }}" class="px-4 py-2 sm:px-6 sm:py-2.5 border-2 text-white rounded-lg font-semibold transition-all text-sm sm:text-base" style="border-color: #0e2382; color: #0e2382;">Sign In</a>
                        <a href="{{ route('login') }}" class="px-4 py-2 sm:px-6 sm:py-2.5 text-white rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="background-color: #0e2382;">Sign In</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 sm:px-6 sm:py-2.5 text-white rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="background-color: #0e2382;">Dashboard</a>
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
                    <a href="{{ route('login') }}" class="block px-4 py-2 border-2 rounded-lg font-semibold text-center" style="border-color: #0e2382; color: #0e2382;">Sign In</a>
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-white rounded-lg font-semibold text-center" style="background-color: #0e2382;">Sign In</a>
                @else
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-white rounded-lg font-semibold text-center" style="background-color: #0e2382;">Dashboard</a>
                @endguest
            </div>
        </nav>
    </header>

    <!-- Pricing Section -->
    <section class="py-12 sm:py-16 md:py-20 bg-white">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6">
            <div class="text-center mb-12">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-3 sm:mb-4">Simple, Transparent Pricing</h1>
                <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto">Choose a package to get better SMS rates. Packages are free - you only pay for the SMS you send.</p>
                <div class="mt-6 inline-block px-6 py-3 bg-accent-50 rounded-lg border-2 border-primary-200">
                    <p class="text-sm sm:text-base text-gray-700"><span class="font-semibold text-primary-900">Default Rate (No Package):</span> <span class="text-2xl font-bold text-primary-700">30 UGX</span> <span class="text-gray-600">per SMS</span></p>
                </div>
            </div>
            
            @if($packages->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 max-w-6xl mx-auto">
                @foreach($packages as $index => $package)
                <div class="bg-white border-2 {{ $index === 1 ? 'border-primary-500' : 'border-gray-200' }} rounded-2xl p-6 sm:p-8 hover:shadow-xl hover:border-primary-500 transition-all {{ $index === 1 ? 'relative' : '' }}">
                    @if($index === 1)
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <span class="bg-primary-500 text-white px-4 py-1 rounded-full text-xs font-semibold">POPULAR</span>
                    </div>
                    @endif
                    
                    <div class="text-center mb-6">
                        <h3 class="text-xl sm:text-2xl font-bold mb-2">{{ $package->name }}</h3>
                        @if($package->description)
                        <p class="text-sm text-gray-500 mb-4">{{ $package->description }}</p>
                        @endif
                        <div class="mb-6 space-y-3">
                            @if($package->price > 0)
                            <div class="px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 rounded-lg text-white">
                                <div class="text-xs opacity-90 mb-1">Package Price</div>
                                <div class="text-3xl font-bold mb-1">{{ number_format($package->price, 0) }}</div>
                                <div class="text-sm opacity-90">UGX</div>
                            </div>
                            @else
                            <div class="px-4 py-2 bg-accent-50 rounded-lg border border-primary-200">
                                <div class="text-sm text-primary-700 font-semibold">FREE Package</div>
                            </div>
                            @endif
                            <div class="px-4 py-3 bg-accent-50 rounded-lg border-2 border-primary-200">
                                <div class="text-xs text-primary-600 mb-1">Cost Per SMS</div>
                                <div class="text-3xl font-bold text-primary-700 mb-1">
                                    {{ number_format((float)($package->cost_per_sms ?? 30.00), 0) }}
                                </div>
                                <div class="text-sm text-primary-600">UGX per SMS</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($package->features && count($package->features) > 0)
                    <ul class="space-y-3 mb-8">
                        @foreach($package->features as $feature)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-500 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    
                    @guest
                        <a href="{{ route('login') }}" class="block w-full text-center py-3 px-6 {{ $index === 1 ? 'text-white' : 'bg-gray-100 text-gray-800' }} rounded-lg font-semibold {{ $index === 1 ? '' : 'hover:bg-gray-200' }} transition-colors" style="{{ $index === 1 ? 'background-color: #0e2382;' : '' }}">
                            Sign In
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="block w-full text-center py-3 px-6 {{ $index === 1 ? 'text-white' : 'bg-gray-100 text-gray-800' }} rounded-lg font-semibold {{ $index === 1 ? '' : 'hover:bg-gray-200' }} transition-colors" style="{{ $index === 1 ? 'background-color: #0e2382;' : '' }}">
                            View Dashboard
                        </a>
                    @endguest
                </div>
                @endforeach
            </div>
            @else
            <!-- Default pricing if no packages -->
            <div class="max-w-2xl mx-auto text-center py-12">
                <p class="text-gray-600 mb-4">Packages are being configured. Default rate is 30 UGX per SMS.</p>
                @guest
                <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                    Sign In
                </a>
                @else
                <a href="{{ route('dashboard') }}" class="inline-block px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                    View Dashboard
                </a>
                @endguest
            </div>
            @endif
            
            <!-- Pricing Table -->
            @if($packages->count() > 0)
            <div class="mt-12 max-w-4xl mx-auto">
                <h3 class="text-xl font-bold text-center mb-6">SMS Rates by Package</h3>
                @include('pricing-table', ['packages' => $packages])
            </div>
            @endif
            
            <div class="mt-12 text-center">
                <p class="text-sm text-gray-600 mb-4">Packages are free - no payment required. Select a package to get better SMS rates. Default rate for businesses without a package is 30 UGX per SMS.</p>
                <p class="text-xs text-gray-500">Need help choosing? <a href="mailto:info@sms.wearemarz.com" class="text-primary-600 hover:text-primary-700 underline">Contact us</a> for personalized recommendations.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="text-white py-12 sm:py-16 md:py-20 text-center" style="background-color: #0e2382;">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4">Ready to Sign In?</h2>
            <p class="text-base sm:text-lg mb-6 sm:mb-8 opacity-95">Join thousands of businesses already using GreensWell to connect with their customers</p>
            @guest
                <a href="{{ route('login') }}" class="inline-block w-full sm:w-auto px-6 sm:px-8 py-2.5 sm:py-3 bg-white rounded-lg font-semibold hover:shadow-xl hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="color: #0e2382;">Sign In</a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-block w-full sm:w-auto px-6 sm:px-8 py-2.5 sm:py-3 bg-white rounded-lg font-semibold hover:shadow-xl hover:-translate-y-0.5 transition-all text-sm sm:text-base" style="color: #0e2382;">Go to Dashboard</a>
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

