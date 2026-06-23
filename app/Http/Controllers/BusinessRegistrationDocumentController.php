<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessRegistrationDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BusinessRegistrationDocumentController extends Controller
{
    public function download(Business $business, BusinessRegistrationDocument $document)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $isSuperAdmin = (int) $user->business_id === 1;
        $isOwnBusiness = (int) $user->business_id === (int) $business->id;

        if (! $isSuperAdmin && ! $isOwnBusiness) {
            abort(403, 'Unauthorized.');
        }

        if ((int) $document->business_id !== (int) $business->id) {
            abort(404);
        }

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path)
        );
    }
}
