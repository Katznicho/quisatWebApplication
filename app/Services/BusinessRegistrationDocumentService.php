<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessRegistrationDocument;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BusinessRegistrationDocumentService
{
    public function authorizeManage(User $user, Business $business): void
    {
        $isSuperAdmin = (int) $user->business_id === 1;
        $isOwnBusiness = (int) $user->business_id === (int) $business->id;

        if (! $isSuperAdmin && ! $isOwnBusiness) {
            abort(403, 'Unauthorized.');
        }
    }

    public function documentTypesFor(Business $business): Collection
    {
        if (! $business->businessCategory) {
            return collect();
        }

        return $business->businessCategory
            ->requiredDocumentTypesForAccount('business');
    }

    /**
     * @param  array<int, UploadedFile>  $files  keyed by document_type_id
     * @return array<int, BusinessRegistrationDocument>
     */
    public function uploadMany(Business $business, array $files): array
    {
        $allowedTypeIds = $this->documentTypesFor($business)->pluck('id')->all();
        $uploaded = [];

        foreach ($files as $documentTypeId => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $documentTypeId = (int) $documentTypeId;

            if (! in_array($documentTypeId, $allowedTypeIds, true)) {
                throw ValidationException::withMessages([
                    "documents.{$documentTypeId}" => 'This document type is not required for this business category.',
                ]);
            }

            $uploaded[] = $this->uploadOne($business, $documentTypeId, $file);
        }

        if ($uploaded !== []) {
            $business->update(['registration_verified_at' => null]);
        }

        return $uploaded;
    }

    public function uploadOne(Business $business, int $documentTypeId, UploadedFile $file): BusinessRegistrationDocument
    {
        DocumentType::query()
            ->whereKey($documentTypeId)
            ->where('is_active', true)
            ->firstOrFail();

        $existing = BusinessRegistrationDocument::query()
            ->where('business_id', $business->id)
            ->where('document_type_id', $documentTypeId)
            ->first();

        if ($existing?->file_path && Storage::disk('public')->exists($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $storedPath = $file->store('business_registration_documents', 'public');

        $attributes = [
            'file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
        ];

        if ($existing) {
            $existing->update($attributes);

            return $existing->fresh();
        }

        return BusinessRegistrationDocument::create([
            'business_id' => $business->id,
            'document_type_id' => $documentTypeId,
            ...$attributes,
        ]);
    }
}
