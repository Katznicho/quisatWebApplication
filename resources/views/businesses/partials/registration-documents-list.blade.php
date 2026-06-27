@php
    $documents = $business->registrationDocuments
        ->sortBy(fn ($doc) => $doc->documentType?->sort_order ?? 0)
        ->values();
@endphp

@if ($documents->isNotEmpty())
    <div class="mb-6">
        <p class="text-sm font-medium text-gray-900 mb-3">Documents on file</p>
        <div class="space-y-2">
            @foreach ($documents as $document)
                <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">{{ $document->documentType?->name ?? 'Document' }}</p>
                        <p class="text-gray-600 truncate">{{ $document->original_filename }}</p>
                        <p class="text-xs text-gray-400">Uploaded {{ $document->updated_at?->format('M d, Y') }}</p>
                    </div>
                    <a href="{{ route('businesses.registration-documents.download', [$business, $document]) }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-blue-600 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                        Download
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
