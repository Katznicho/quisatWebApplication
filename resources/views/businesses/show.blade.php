<x-app-layout>
    <div class="py-12 max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">View Document</h2>

            <div class="mb-4">
                <strong class="text-gray-700 dark:text-gray-300">Title:</strong>
                <p class="text-gray-900 dark:text-white">{{ $document->title }}</p>
            </div>

            @if ($document->description)
                <div class="mb-4">
                    <strong class="text-gray-700 dark:text-gray-300">Description:</strong>
                    <p class="text-gray-900 dark:text-white">{{ $document->description }}</p>
                </div>
            @endif

            <div class="mb-4">
                <strong class="text-gray-700 dark:text-gray-300">Uploaded By:</strong>
                <p class="text-gray-900 dark:text-white">{{ $document->user->name }}</p>
            </div>

            <div class="mb-4">
                <strong class="text-gray-700 dark:text-gray-300">Business:</strong>
                <p class="text-gray-900 dark:text-white">{{ $document->business->name ?? '-' }}</p>
            </div>

            <div class="mb-6">
                <strong class="text-gray-700 dark:text-gray-300">File:</strong><br>
                <a href="{{ $document->file_url }}" target="_blank"
                   class="inline-block mt-2 text-blue-600 underline">📄 View / Download Document</a>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('business-documents.edit', $document) }}"
                   class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Edit</a>

                <a href="{{ route('dashboard') }}"
                   class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back</a>
            </div>
        </div>
    </div>
</x-app-layout>
