<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Registration Successful!
                </h2>
                
                <p class="mt-2 text-sm text-gray-600">
                    Your business has been registered successfully with {{ config('app.name') }}
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="space-y-4">
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">What's Next?</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-100">
                                    <span class="text-sm font-medium text-blue-600">1</span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-700">
                                    <strong>Check your email</strong> for verification links
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-100">
                                    <span class="text-sm font-medium text-blue-600">2</span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-700">
                                    <strong>Verify your email address</strong> to activate your account
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-100">
                                    <span class="text-sm font-medium text-blue-600">3</span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-700">
                                    <strong>Log in</strong> to access your business dashboard
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col space-y-3">
                <a href="{{ route('login') }}" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Go to Login
                </a>
                
                <a href="{{ route('business.register') }}" 
                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Register Another Business
                </a>
            </div>

            <div class="text-center">
                <p class="text-xs text-gray-500">
                    Need help? Contact our support team at 
                    <a href="mailto:support@{{ config('app.domain', 'example.com') }}" class="text-indigo-600 hover:text-indigo-500">
                        support@{{ config('app.domain', 'example.com') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
