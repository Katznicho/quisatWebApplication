@extends('layouts.app')

@section('title', 'Our Portfolio | Risidev')

@section('content')
    <!-- Enhanced Hero Section -->
    <div class="relative overflow-hidden bg-[#002174]">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-0 left-0 w-72 h-72 bg-blue-400/10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-300/10 rounded-full translate-x-1/3 translate-y-1/3 blur-3xl"></div>
            <div class="absolute inset-y-0 right-0 w-1/3 bg-gradient-to-l from-blue-500/10 to-transparent"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Text Content -->
                <div class="text-white space-y-8">
                    <div class="inline-flex items-center px-4 py-2 bg-white/10 rounded-full backdrop-blur-sm">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        <span class="ml-3 text-sm font-medium">Available for New Projects</span>
                    </div>
                    
                    <h1 class="text-4xl lg:text-6xl font-bold leading-tight">
                        Transforming Ideas into
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 via-blue-400 to-blue-300">
                            Digital Excellence
                        </span>
                    </h1>
                    
                    <p class="text-xl text-white/80 leading-relaxed">
                        Explore our portfolio of successful projects where innovation meets functionality. Each solution is crafted with precision and care to deliver exceptional results.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-4">
                        <div class="bg-white/5 backdrop-blur-sm px-6 py-4 rounded-xl border border-white/10">
                            <div class="text-3xl font-bold">20+</div>
                            <div class="text-white/70">Projects Completed</div>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm px-6 py-4 rounded-xl border border-white/10">
                            <div class="text-3xl font-bold">15+</div>
                            <div class="text-white/70">Happy Clients</div>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm px-6 py-4 rounded-xl border border-white/10">
                            <div class="text-3xl font-bold">100%</div>
                            <div class="text-white/70">Client Satisfaction</div>
                        </div>
                    </div>
                </div>

                <!-- Visual Element -->
                <div class="relative hidden lg:block">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-transparent rounded-3xl"></div>
                    <div class="grid grid-cols-2 gap-4 p-8">
                        <div class="space-y-4">
                            <div class="h-32 bg-white/10 rounded-xl backdrop-blur-sm animate-pulse"></div>
                            <div class="h-48 bg-white/5 rounded-xl backdrop-blur-sm"></div>
                        </div>
                        <div class="space-y-4 pt-8">
                            <div class="h-48 bg-white/5 rounded-xl backdrop-blur-sm"></div>
                            <div class="h-32 bg-white/10 rounded-xl backdrop-blur-sm animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rest of the portfolio content remains unchanged -->
    <div class="relative py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">Our Featured Projects</h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">Discover how we've helped organizations transform their ideas into powerful digital solutions.</p>
            </div>
        </div>
    </div>

    <!-- Portfolio Grid -->
    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Our Featured Projects</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Discover how we've helped organizations transform their ideas into powerful digital solutions.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- 1. Omukutu Platform -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1625246333195-78d9c38ad449?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                             alt="Omukutu Platform" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://omukutu.risidev.com/" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Platform
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Omukutu</h3>
                        <p class="text-gray-600 mb-6">A digital agricultural platform that connects farmers to markets, finance, inputs, advisory, and logistics in one integrated system. It serves as coordination infrastructure for agricultural commerce—helping farmers know when and where to sell, access affordable inputs and financial services, receive climate-smart guidance, and link to buyers and service providers—starting with maize and scaling across value chains and regions.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Agriculture</span>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Marketplace</span>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Finance</span>
                        </div>
                    </div>
                </div>

                <!-- 2. FundPoint Platform -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.pexels.com/photos/33245420/pexels-photo-33245420.jpeg?_gl=1*uwgjoj*_ga*MTExNjE3OTE5LjE3NDMyODk2Njg.*_ga_8JE65Q40S6*czE3NzA5MTUwOTQkbzE4JGcxJHQxNzcwOTE3NjgzJGo0MyRsMCRoMA" 
                             alt="FundPoint Platform" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://fundpoint.risidev.com/en" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Platform
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">FundPoint</h3>
                        <p class="text-gray-600 mb-6">A comprehensive fundraising and ticketing platform designed to facilitate donation campaigns and sell event tickets. This innovative solution enables organizers to reach wider audiences while making it easy for supporters to contribute to causes they care about or attend events seamlessly. Built with multi-language support and a focus on empowering change through digital generosity.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Fundraising</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Event Ticketing</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Multi-language</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Uganda Data Project -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1581089781785-603411fa81e5?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                             alt="Uganda Data Project" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://ugandadatadocs.netlify.app/" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Website
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Uganda Data Project</h3>
                        <p class="text-gray-600 mb-6">A comprehensive platform providing access to detailed data about Uganda's administrative divisions, including districts, counties, sub-counties, parishes, and villages. Built for researchers, organizations, and individuals seeking accurate insights about Uganda.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Data Analytics</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">React</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">API Integration</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Reuse Platform -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.pexels.com/photos/17072315/pexels-photo-17072315.jpeg?_gl=1*1gcdm5t*_ga*MTExNjE3OTE5LjE3NDMyODk2Njg.*_ga_8JE65Q40S6*czE3NzA5MTUwOTQkbzE4JGcxJHQxNzcwOTE3Nzk0JGo0JGwwJGgw" 
                             alt="Reuse Platform" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://reuse.risidev.com/admin/login" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Platform
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Reuse Platform</h3>
                        <p class="text-gray-600 mb-6">An innovative donation management platform connecting donors with communities in need. This solution streamlines the process of giving and receiving support, creating a seamless bridge between generosity and impact.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Laravel</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Vue.js</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">MySQL</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Muclass Platform -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1620829813573-7c9e1877706f?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" 
                             alt="Muclass Platform" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://muclass.risidev.com/" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Platform
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Muclass</h3>
                        <p class="text-gray-600 mb-6">A digital learning and certification platform that delivers hands-on, practical skills training and validates competencies through structured assessment and certification. It connects learners, trainers, and industry partners to build job-ready capabilities, support workforce development, and improve access to recognized skills credentials across sectors.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">E-Learning</span>
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">Certification</span>
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">Skills Training</span>
                        </div>
                    </div>
                </div>

                <!-- 6. JobTrust Platform -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.pexels.com/photos/11174198/pexels-photo-11174198.jpeg?_gl=1*s1rzhk*_ga*MTExNjE3OTE5LjE3NDMyODk2Njg.*_ga_8JE65Q40S6*czE3NzA5MTUwOTQkbzE4JGcxJHQxNzcwOTE3MDg5JGo0MCRsMCRoMA.." 
                             alt="JobTrust Platform" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://jobtrust.space/jobs" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Platform
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">JobTrust</h3>
                        <p class="text-gray-600 mb-6">Find Your Dream Job Today. Discover thousands of job opportunities from verified employers across Uganda. Your next career move starts here with a trusted platform connecting job seekers with quality employment opportunities.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">Job Board</span>
                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">Career</span>
                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">Recruitment</span>
                        </div>
                    </div>
                </div>

                <!-- 7. TrustJobs Platform -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="relative">
                        <img src="https://images.pexels.com/photos/19218034/pexels-photo-19218034.jpeg?_gl=1*1kgvf9r*_ga*MTExNjE3OTE5LjE3NDMyODk2Njg.*_ga_8JE65Q40S6*czE3NzA5MTUwOTQkbzE4JGcxJHQxNzcwOTE2OTc0JGo0JGwwJGgw" 
                             alt="TrustJobs Platform" 
                             class="w-full h-64 object-cover">
                        <div class="absolute inset-0 bg-[#002174]/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="https://trustjobhub.com/" target="_blank" 
                               class="bg-white text-[#002174] px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                Visit Platform
                            </a>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">TrustJobs</h3>
                        <p class="text-gray-600 mb-6">A digital talent and recruitment platform that connects professionals to employment opportunities while helping organizations and companies identify, assess, and hire qualified talent efficiently. It streamlines job matching, verification, and placement to improve workforce access and support employers in building reliable, skilled teams.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">Talent Platform</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">Recruitment</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">Job Matching</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection