<x-app-layout>
    <div class="py-12 max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Edit Document</h2>

            <form method="POST" action="{{ route('business-documents.update', $document) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Title</label>
                    <input type="text" name="title" id="title" required
                        value="{{ old('title', $document->title) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @error('title')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description', $document->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="document_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Replace Document (optional)</label>
                    <input type="file" name="document_file" id="document_file" accept=".pdf,.jpg,.jpeg,.png"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep the existing file.</p>
                    @error('document_file')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="submit"
                        class="bg-[#011478] text-white px-4 py-2 rounded hover:bg-[#011478]/90">
                        Update Document
                    </button>

                    <a href="{{ route('business-documents.show', $document) }}"
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
