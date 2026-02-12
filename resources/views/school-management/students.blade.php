<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show"
                        class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 transition"
                        role="alert">
                        <span class="block sm:inline whitespace-pre-line">{{ session('success') }}</span>
                        <button @click="show = false"
                            class="absolute top-1 right-2 text-xl font-semibold text-green-700">
                            &times;
                        </button>
                    </div>
                @endif

                @if (session('bulk_upload_errors') && count(session('bulk_upload_errors')) > 0)
                    <div x-data="{ show: true }" x-show="show"
                        class="relative bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 transition"
                        role="alert">
                        <strong class="font-bold">Bulk Upload Errors:</strong>
                        <ul class="list-disc list-inside mt-2">
                            @foreach (array_slice(session('bulk_upload_errors'), 0, 5) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @if (count(session('bulk_upload_errors')) > 5)
                                <li>... and {{ count(session('bulk_upload_errors')) - 5 }} more errors</li>
                            @endif
                        </ul>
                        <button @click="show = false"
                            class="absolute top-1 right-2 text-xl font-semibold text-yellow-700">
                            &times;
                        </button>
                    </div>
                @endif

                <div class="mb-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Students</h2>
                    <div class="flex space-x-3">
                        <a href="{{ route('school-management.students.create') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Student
                        </a>
                        <a href="{{ route('school-management.students.bulk-upload-page') }}" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Bulk Upload
                        </a>
                    </div>
                </div>

                @livewire('school-management.student-management')
            </div>
        </div>

    </div>
</x-app-layout>
