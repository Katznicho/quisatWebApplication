<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Login adverts</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Shown once after a parent or staff member signs in, for 10 seconds, then the dashboard opens. If none is active, sign-in goes straight to the dashboard.</p>
                @livewire('login-advert-management')
            </div>
        </div>
    </div>
</x-app-layout>
