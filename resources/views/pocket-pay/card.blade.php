<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                @include('pocket-pay.partials.nav')
            </div>
            @livewire('pocket-pay.card-detail', ['card' => $card])
        </div>
    </div>
</x-app-layout>
