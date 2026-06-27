@php
    $documentTypes = app(\App\Services\BusinessRegistrationDocumentService::class)->documentTypesFor($business);
    $uploadedByType = $business->registrationDocuments->keyBy('document_type_id');
    $forAdmin = $forAdmin ?? false;
@endphp

<div class="space-y-4">
    @if ($forAdmin)
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Upload documents on behalf of <strong>{{ $business->name }}</strong>.
            They will appear on the business profile where the business can view, download, and upload more.
        </p>
    @endif

    @if ($documentTypes->isEmpty())
        <p class="text-sm text-gray-500">
            No document requirements are configured for your business category.
            Contact Quisat support if you need to submit verification documents.
        </p>
    @else
        <form method="POST"
            action="{{ route('businesses.registration-documents.store', $business) }}"
            enctype="multipart/form-data"
            class="space-y-4">
            @csrf

            @foreach ($documentTypes as $documentType)
                @php
                    $existing = $uploadedByType->get($documentType->id);
                    $isRequired = (bool) ($documentType->pivot->is_required ?? $documentType->is_required);
                @endphp
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $documentType->name }}
                                @if ($isRequired)
                                    <span class="text-red-600">*</span>
                                @endif
                            </p>
                            @if ($documentType->description)
                                <p class="text-xs text-gray-500 mt-1">{{ $documentType->description }}</p>
                            @endif
                        </div>
                        @if ($existing)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                Uploaded
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                Missing
                            </span>
                        @endif
                    </div>

                    @if ($existing)
                        <div class="mb-3 flex flex-col gap-2 rounded-md bg-gray-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-gray-700">{{ $existing->original_filename }}</p>
                                <p class="text-xs text-gray-400">Uploaded {{ $existing->created_at?->format('M d, Y') }}</p>
                            </div>
                            <a href="{{ route('businesses.registration-documents.download', [$business, $existing]) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-blue-600 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                                Download
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 mb-2">Upload a new file below to replace this document.</p>
                    @endif

                    <input type="file"
                        name="documents[{{ $documentType->id }}]"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    @error("documents.{$documentType->id}")
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            @error('documents')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Save documents
                </button>
            </div>
        </form>
    @endif
</div>
