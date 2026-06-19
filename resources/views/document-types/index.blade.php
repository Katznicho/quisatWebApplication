<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Document Types</h2>
                </div>

                @livewire('document-types.list-document-types')
            </div>
        </div>
    </div>
</x-app-layout>
