@extends('layouts.app')

@section('title', 'Contact Us | Risidev')

@section('content')
    <!-- Enhanced Hero Section -->
    <div class="relative overflow-hidden bg-[#002174]">
        <!-- Decorative elements -->
        <div class="absolute inset-0">
            <div class="absolute inset-y-0 left-0 w-1/2 bg-white/5 transform -skew-x-12"></div>
            <div class="absolute top-0 right-0 w-1/4 h-full bg-white/5 transform skew-x-12"></div>
            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2">
                <div class="h-20 w-20 rounded-full bg-white/10 blur-2xl"></div>
            </div>
        </div>

        <!-- Content -->
        <div class="relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <!-- Left Column -->
                    <div class="text-white space-y-6">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                            Let's Build Impactful Solutions Together
                        </h1>
                        <p class="text-xl text-white/80">
                            Have an idea, partnership, or opportunity to explore? We’re ready to collaborate and turn vision into practical digital solutions that create real change.
                        </p>
                    </div>

                    <!-- Right Column -->
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20">
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Quick Contact Card 1 -->
                            <div class="bg-white/5 p-6 rounded-xl hover:bg-white/10 transition duration-300">
                                <svg class="w-8 h-8 text-blue-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <h3 class="text-lg font-semibold mb-2">Call Us</h3>
                                <p class="text-white/80">0790205056</p>
                            </div>

                            <!-- Quick Contact Card 2 -->
                            <div class="bg-white/5 p-6 rounded-xl hover:bg-white/10 transition duration-300">
                                <svg class="w-8 h-8 text-blue-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <h3 class="text-lg font-semibold mb-2">Email Us</h3>
                                <p class="text-white/80">info@risidev.com</p>
                            </div>

                            <!-- Quick Contact Card 3 - WhatsApp -->
                            <div class="bg-white/5 p-6 rounded-xl hover:bg-white/10 transition duration-300">
                                <a href="https://wa.me/256790205056" target="_blank" class="block">
                                    <svg class="w-8 h-8 text-green-400 mb-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    <h3 class="text-lg font-semibold mb-2">WhatsApp Us</h3>
                                    <p class="text-white/80">0790205056</p>
                                </a>
                            </div>

                            <!-- Quick Contact Card 4 -->
                            <div class="bg-white/5 p-6 rounded-xl hover:bg-white/10 transition duration-300">
                                <svg class="w-8 h-8 text-blue-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <h3 class="text-lg font-semibold mb-2">Visit Us</h3>
                                <p class="text-white/80">Mpererwe, Vero Plaza, Above offices of National Water, Kampala, Uganda</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Previous hero section remains unchanged -->

        <div class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-12">
                    <!-- Contact Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-lg p-8">
                            <h2 class="text-2xl font-bold text-[#002174] mb-6">Send us a message</h2>
                            <form action="" method="POST" class="space-y-6">
                                @csrf
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                        <input type="text" name="name" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#002174] focus:border-transparent" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#002174] focus:border-transparent" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                                    <input type="text" name="subject" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#002174] focus:border-transparent" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                    <textarea name="message" rows="6" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#002174] focus:border-transparent" required></textarea>
                                </div>
                                <button type="submit" class="w-full bg-[#002174] text-white px-8 py-4 rounded-lg hover:bg-[#001a5c] transition-colors font-semibold">
                                    Send Message
                                </button>
                            </form>
                        </div>
                    </div>
    
                    <!-- Contact Info -->
                    <div class="space-y-8">
                        <div class="bg-white rounded-2xl shadow-lg p-8">
                            <h2 class="text-2xl font-bold text-[#002174] mb-6">Contact Information</h2>
                            <div class="space-y-6">
                                <div class="flex items-start gap-4">
                                    <div class="bg-[#002174]/10 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-[#002174]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Phone</h3>
                                        <p class="text-gray-600">0790205056</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="bg-[#002174]/10 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-[#002174]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Email</h3>
                                        <p class="text-gray-600">info@risidev.com</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="bg-[#002174]/10 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">WhatsApp</h3>
                                        <a href="https://wa.me/256790205056" target="_blank" class="text-green-600 hover:text-green-700">0790205056</a>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="bg-[#002174]/10 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-[#002174]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Address</h3>
                                        <p class="text-gray-600">Mpererwe, Vero Plaza, Above offices of National Water, Kampala, Uganda</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Google Maps -->
                <div class="mt-12 rounded-2xl overflow-hidden shadow-lg h-[400px]">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7538457337447!2d32.58387!3d0.3824!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177dbb7c4f1b5555%3A0x7c6be3a9fd12b12d!2sMpererwe!5e0!3m2!1sen!2sug!4v1234567890"
                        width="100%"
                        height="400"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
@endsection