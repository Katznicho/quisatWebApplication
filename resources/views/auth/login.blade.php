<x-authentication-layout>
    <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-gray-800 dark:text-white">Welcome back to {{env('APP_NAME')}}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Secure access to your payment dashboard</p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div>
            <x-label for="email" value="Email Address" />
            <x-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="you@example.com" />
        </div>

        <div>
            <x-label for="password" value="Password" />
            <x-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
        </div>

        <div class="flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-blue-600 dark:text-blue-400 hover:underline" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <div>
            <x-button class="w-full justify-center">
                🔐 Sign in
            </x-button>
        </div>

        <x-validation-errors class="mt-4" />
    </form>
</x-authentication-layout>
