@php
    $documents = $business->registrationDocuments->sortBy(fn ($doc) => $doc->documentType?->sort_order ?? 0);
    $admin = $business->users()->whereHas('role', fn ($q) => $q->where('name', 'Admin'))->first()
        ?? $business->users()->oldest()->first();
@endphp

<div class="space-y-5 text-sm text-gray-700 dark:text-gray-300">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">Business</p>
            <p>{{ $business->name }}</p>
            <p>{{ $business->email }}</p>
            <p>{{ $business->phone }}</p>
            <p class="mt-1 text-gray-500">{{ $business->businessCategory?->name ?? 'No category' }}</p>
        </div>
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">Verification status</p>
            @if ($business->isRegistrationVerified())
                <p class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                    Verified {{ $business->registration_verified_at?->format('M d, Y H:i') }}
                </p>
            @elseif ($documents->isNotEmpty())
                <p class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                    Pending review ({{ $documents->count() }} document{{ $documents->count() === 1 ? '' : 's' }})
                </p>
            @else
                <p class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                    No documents submitted
                </p>
            @endif
            <p class="mt-3 text-gray-500">Registered {{ $business->created_at?->format('M d, Y H:i') }}</p>
            @if ($admin)
                <p class="mt-2"><span class="font-medium text-gray-900 dark:text-white">Admin:</span> {{ $admin->name }} ({{ $admin->email }})</p>
            @endif
        </div>
    </div>

    @if ($documents->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-6 text-center text-gray-500">
            This business has not uploaded any registration documents yet.
        </div>
    @else
        <div>
            <p class="mb-3 font-semibold text-gray-900 dark:text-white">Submitted documents</p>
            <div class="space-y-3">
                @foreach ($documents as $document)
                    @php
                        $typeName = $document->documentType?->name ?? 'Document';
                        $downloadUrl = route('businesses.registration-documents.download', [$business, $document]);
                        $previewUrl = $document->fileUrl();
                        $isImage = $document->isImage();
                        $isPdf = $document->isPdf();
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $typeName }}</p>
                                @if ($document->documentType?->description)
                                    <p class="mt-1 text-xs text-gray-500">{{ $document->documentType->description }}</p>
                                @endif
                                <p class="mt-2 break-all text-gray-600 dark:text-gray-400">{{ $document->original_filename }}</p>
                                <p class="text-xs text-gray-400">Uploaded {{ $document->created_at?->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="{{ $downloadUrl }}"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                                    target="_blank" rel="noopener noreferrer">
                                    Download
                                </a>
                                @if ($isImage && $previewUrl)
                                    <a href="{{ $previewUrl }}"
                                        class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                        target="_blank" rel="noopener noreferrer">
                                        View image
                                    </a>
                                @elseif ($isPdf && $previewUrl)
                                    <a href="{{ $previewUrl }}"
                                        class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                        target="_blank" rel="noopener noreferrer">
                                        View PDF
                                    </a>
                                @endif
                            </div>
                        </div>
                        @if ($isImage && $previewUrl)
                            <div class="mt-3">
                                <img src="{{ $previewUrl }}" alt="{{ $typeName }}"
                                    class="max-h-48 rounded border border-gray-200 object-contain dark:border-gray-700">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (auth()->user()?->business_id === 1)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
            <p class="mb-3 font-semibold text-gray-900 dark:text-white">Upload or replace documents on behalf of this business</p>
            @include('businesses.partials.registration-documents-form', [
                'business' => $business,
                'forAdmin' => true,
            ])
        </div>
    @endif
</div>
