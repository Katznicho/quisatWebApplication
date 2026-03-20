<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassAssignment;
use App\Models\ParentGuardian;
use App\Models\SchoolDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->get('business');

        $schoolDocuments = SchoolDocument::query()
            ->where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (SchoolDocument $doc) {
                return [
                    'id' => $doc->id,
                    'assignment_id' => null,
                    'assignment_title' => $doc->title,
                    'class_room' => null,
                    'subject' => null,
                    'due_date' => null,
                    'name' => $doc->title,
                    'url' => $doc->file_url ?: ($doc->file_path ? asset('storage/'.$doc->file_path) : null),
                    'type' => $doc->type,
                    'size' => $doc->size ? $this->formatSize($doc->size) : null,
                ];
            });

        $assignmentDocuments = ClassAssignment::query()
            ->with(['classRoom:id,name,code', 'subject:id,name'])
            ->where('business_id', $business->id)
            ->whereNotNull('attachments')
            ->orderByDesc('created_at')
            ->get()
            ->flatMap(function (ClassAssignment $assignment) {
                return collect($assignment->attachments ?? [])->map(function ($attachment) use ($assignment) {
                    return [
                        'id' => null,
                        'assignment_id' => $assignment->id,
                        'assignment_title' => $assignment->title,
                        'class_room' => $assignment->classRoom?->name,
                        'subject' => $assignment->subject?->name,
                        'due_date' => optional($assignment->due_date)->toIso8601String(),
                        'name' => $attachment['name'] ?? 'Document',
                        'url' => $attachment['url'] ?? null,
                        'type' => $attachment['type'] ?? null,
                        'size' => $attachment['size'] ?? null,
                    ];
                });
            });

        $documents = $schoolDocuments
            ->concat($assignmentDocuments)
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved successfully.',
            'data' => [
                'documents' => $documents,
            ],
        ]);
    }

    /**
     * Upload a general school document (circulars, calendars, etc.).
     */
    public function store(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (! $business || ! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Only authenticated staff can upload documents.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'type' => 'required|string|in:circular,calendar,policy,other',
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
        $directory = 'school-documents/'.$business->id;
        $path = $directory.'/'.$filename;

        Storage::disk('public')->put($path, $binary);

        $doc = SchoolDocument::create([
            'business_id' => $business->id,
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

        $documentItem = [
            'id' => $doc->id,
            'assignment_id' => null,
            'assignment_title' => $doc->title,
            'class_room' => null,
            'subject' => null,
            'due_date' => null,
            'name' => $doc->title,
            'url' => $doc->file_url,
            'type' => $doc->type,
            'size' => $doc->size ? $this->formatSize($doc->size) : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'data' => [
                'document' => $documentItem,
            ],
        ], 201);
    }

    protected function formatSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return sprintf('%.1f %s', $value, $units[$power]);
    }

    /**
     * Delete a school-wide document.
     * Both staff and parents can delete as requested.
     */
    public function destroy(Request $request, SchoolDocument $document)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (! $business || $document->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found in your business.',
            ], 404);
        }

        // Only authenticated users inside the same business can delete.
        if (! ($user instanceof User) && ! ($user instanceof ParentGuardian)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this document.',
            ], 403);
        }

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

