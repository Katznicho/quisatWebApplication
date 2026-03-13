<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentDocumentController extends Controller
{
    /**
     * List documents for a specific student.
     */
    public function index(Request $request, Student $student)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (! $business || $student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        if ($user instanceof ParentGuardian) {
            $isChild = $user->students()->whereKey($student->id)->exists();
            if (! $isChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view documents for this student.',
                ], 403);
            }
        }

        $documents = StudentDocument::query()
            ->with('uploader:id,name')
            ->where('business_id', $business->id)
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (StudentDocument $doc) {
                return [
                    'id' => $doc->id,
                    'uuid' => $doc->uuid,
                    'type' => $doc->type,
                    'title' => $doc->title,
                    'description' => $doc->description,
                    'url' => $doc->file_url ?: ($doc->file_path ? asset('storage/'.$doc->file_path) : null),
                    'mime_type' => $doc->mime_type,
                    'size' => $doc->size,
                    'uploaded_at' => optional($doc->created_at)->toIso8601String(),
                    'uploaded_by' => $doc->uploader ? [
                        'id' => $doc->uploader->id,
                        'name' => $doc->uploader->name,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Student documents loaded successfully.',
            'data' => [
                'documents' => $documents,
            ],
        ]);
    }

    /**
     * Upload a document for a specific student (staff only).
     */
    public function store(Request $request, Student $student)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (! $business || $student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Only staff users can upload documents.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'type' => 'required|string|in:report,invoice,letter,other',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required|array',
                'file.name' => 'required|string|max:255',
                'file.base64' => 'required|string',
                'file.mime_type' => 'required|string|max:255',
                'file.size' => 'nullable|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $fileData = $validated['file'];
        $base64 = $fileData['base64'];

        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $binary = base64_decode($base64, true);

        if ($binary === false) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file payload.',
            ], 422);
        }

        $originalName = $fileData['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
        $filename = Str::uuid() . '_' . time() . '.' . $extension;
        $directory = 'student-documents/'.$business->id;
        $path = $directory.'/'.$filename;

        Storage::disk('public')->put($path, $binary);

        $document = StudentDocument::create([
            'business_id' => $business->id,
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_url' => asset('storage/'.$path),
            'mime_type' => $fileData['mime_type'],
            'size' => $fileData['size'] ?? strlen($binary),
            'meta' => [
                'original_name' => $originalName,
            ],
        ]);

        $document->load('uploader:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'data' => [
                'document' => [
                    'id' => $document->id,
                    'uuid' => $document->uuid,
                    'type' => $document->type,
                    'title' => $document->title,
                    'description' => $document->description,
                    'url' => $document->file_url,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                    'uploaded_at' => optional($document->created_at)->toIso8601String(),
                    'uploaded_by' => $document->uploader ? [
                        'id' => $document->uploader->id,
                        'name' => $document->uploader->name,
                    ] : null,
                ],
            ],
        ], 201);
    }

    /**
     * Delete a document for a specific student.
     * Both staff and parents (for their own children) can delete, and the file is removed from storage.
     */
    public function destroy(Request $request, Student $student, StudentDocument $document)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (! $business || $student->business_id !== $business->id || $document->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student or document not found in your business.',
            ], 404);
        }

        if ($document->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'This document does not belong to the specified student.',
            ], 403);
        }

        // Parents can only delete documents for their own children
        if ($user instanceof ParentGuardian) {
            $isChild = $user->students()->whereKey($student->id)->exists();
            if (! $isChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete documents for this student.',
                ], 403);
            }
        } elseif (! $user instanceof User) {
            // Only staff User accounts (and parents above) are allowed
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this document.',
            ], 403);
        }

        // Delete file from storage if present
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }
}

