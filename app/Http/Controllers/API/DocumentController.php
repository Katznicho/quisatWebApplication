<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassAssignment;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->get('business');

        $documents = ClassAssignment::query()
            ->with(['classRoom:id,name,code', 'subject:id,name'])
            ->where('business_id', $business->id)
            ->whereNotNull('attachments')
            ->orderByDesc('created_at')
            ->get()
            ->flatMap(function (ClassAssignment $assignment) {
                return collect($assignment->attachments ?? [])->map(function ($attachment) use ($assignment) {
                    return [
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
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved successfully.',
            'data' => [
                'documents' => $documents,
            ],
        ]);
    }
}
