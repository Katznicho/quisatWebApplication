<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessRegistrationDocument;
use App\Services\BusinessRegistrationDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessRegistrationDocumentController extends Controller
{
    public function __construct(
        protected BusinessRegistrationDocumentService $documents
    ) {
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        $user = Auth::user();
        $this->documents->authorizeManage($user, $business);

        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $files = [];
        foreach ($request->file('documents', []) as $documentTypeId => $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                $files[(int) $documentTypeId] = $file;
            }
        }

        if ($files === []) {
            return back()->with('error', 'Please select at least one file to upload.');
        }

        $this->documents->uploadMany($business, $files);

        $uploadedForAnotherBusiness = (int) $user->business_id === 1
            && (int) $user->business_id !== (int) $business->id;

        if ($uploadedForAnotherBusiness) {
            return redirect()
                ->route('businesses.index')
                ->with('success', "Registration documents saved for {$business->name}. The business can view and upload more from their profile.");
        }

        return back()->with('success', 'Registration documents saved. Quisat will review them shortly.');
    }

    public function download(Business $business, BusinessRegistrationDocument $document)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $this->documents->authorizeManage($user, $business);

        if ((int) $document->business_id !== (int) $business->id) {
            abort(404);
        }

        if (! $document->file_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path)
        );
    }
}
