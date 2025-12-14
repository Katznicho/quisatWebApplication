<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClassAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = ClassAssignment::query()
            ->with([
                'classRoom:id,name,code',
                'subject:id,name,code',
                'teacher:id,name,email',
                'branch:id,name,code',
            ])
            ->where('business_id', $businessId)
            ->orderByDesc('published_at')
            ->orderByDesc('due_date');

        if ($assignmentType = $request->query('type')) {
            $query->where('assignment_type', $assignmentType);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($classRoomId = $request->query('class_room_id')) {
            $query->where('class_room_id', $classRoomId);
        }

        if ($subjectId = $request->query('subject_id')) {
            $query->where('subject_id', $subjectId);
        }

        if ($request->filled('due_before')) {
            $query->whereDate('due_date', '<=', Carbon::parse($request->query('due_before')));
        }

        if ($request->filled('due_after')) {
            $query->whereDate('due_date', '>=', Carbon::parse($request->query('due_after')));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')->orWhere('branch_id', $user->branch_id);
            });
        }

        $perPage = (int) $request->query('per_page', 25);
        $perPage = $perPage > 0 ? min($perPage, 100) : 25;

        $assignments = $query->paginate($perPage);
        $assignments->getCollection()->transform(fn ($assignment) => $this->transformAssignment($assignment));

        return response()->json([
            'success' => true,
            'message' => 'Assignments fetched successfully.',
            'data' => [
                'assignments' => $assignments->items(),
                'pagination' => [
                    'current_page' => $assignments->currentPage(),
                    'per_page' => $assignments->perPage(),
                    'total' => $assignments->total(),
                    'last_page' => $assignments->lastPage(),
                    'has_more' => $assignments->hasMorePages(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            $businessId = $request->get('business_id');
            $user = $request->get('authenticated_user');

            if (!$user instanceof User) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only authenticated staff can create assignments.',
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'assignment_type' => 'required|in:assignment,classwork,homework,project',
                'class_room_id' => 'required|exists:class_rooms,id',
                'subject_id' => 'nullable|exists:subjects,id',
                'assigned_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'due_time' => 'nullable|date_format:H:i',
                'total_marks' => 'nullable|integer|min:0',
                'status' => 'nullable|in:draft,published',
                'attachments' => 'nullable|array',
                'attachments.*.base64' => 'required_with:attachments.*|string',
                'attachments.*.name' => 'required_with:attachments.*|string',
                'attachments.*.mime_type' => 'required_with:attachments.*|string',
                'attachments.*.size' => 'nullable|integer',
            ]);

            $attachments = [];
            if (isset($validated['attachments']) && is_array($validated['attachments'])) {
                foreach ($validated['attachments'] as $attachmentData) {
                    if (isset($attachmentData['base64']) && isset($attachmentData['mime_type'])) {
                        $base64 = $attachmentData['base64'];
                        $mimeType = $attachmentData['mime_type'];
                        $fileName = $attachmentData['name'] ?? 'file';
                        
                        // Determine file type and directory
                        $fileType = str_starts_with($mimeType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') ? 'docx' :
                                   (str_starts_with($mimeType, 'application/msword') ? 'doc' :
                                   (str_starts_with($mimeType, 'application/pdf') ? 'pdf' :
                                   (str_starts_with($mimeType, 'image/') ? 'image' : 'file')));
                        
                        $directory = 'assignments/files';
                        
                        // Decode base64 and store file
                        $fileContent = base64_decode($base64);
                        $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 
                                    ($fileType === 'docx' ? 'docx' : ($fileType === 'doc' ? 'doc' : ($fileType === 'pdf' ? 'pdf' : 'bin')));
                        $storedFileName = uniqid() . '_' . time() . '.' . $extension;
                        $path = $directory . '/' . $storedFileName;
                        
                        // Store file
                        Storage::disk('public')->put($path, $fileContent);
                        
                        $attachments[] = [
                            'path' => $path,
                            'url' => asset('storage/' . $path),
                            'name' => $fileName,
                            'size' => $attachmentData['size'] ?? strlen($fileContent),
                            'type' => $fileType,
                            'mime_type' => $mimeType,
                        ];
                    }
                }
            }

            $assignment = ClassAssignment::create([
                'business_id' => $businessId,
                'branch_id' => $user->branch_id,
                'class_room_id' => $validated['class_room_id'],
                'subject_id' => $validated['subject_id'] ?? null,
                'teacher_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'assignment_type' => $validated['assignment_type'],
                'assigned_date' => isset($validated['assigned_date']) ? $validated['assigned_date'] : now()->toDateString(),
                'due_date' => $validated['due_date'] ?? null,
                'due_time' => $validated['due_time'] ?? null,
                'total_marks' => $validated['total_marks'] ?? null,
                'status' => $validated['status'] ?? 'published',
                'published_at' => ($validated['status'] ?? 'published') === 'published' ? now() : null,
                'attachments' => !empty($attachments) ? $attachments : null,
            ]);

            $assignment->load(['classRoom:id,name,code', 'subject:id,name,code', 'teacher:id,name,email']);

            return response()->json([
                'success' => true,
                'message' => 'Assignment created successfully.',
                'data' => [
                    'assignment' => $this->transformAssignment($assignment, true),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating assignment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $assignment)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $record = ClassAssignment::query()
            ->with([
                'classRoom:id,name,code',
                'subject:id,name,code',
                'teacher:id,name,email',
                'branch:id,name,code',
            ])
            ->where('business_id', $businessId)
            ->where(function ($q) use ($assignment) {
                $q->where('uuid', $assignment);
                if (is_numeric($assignment)) {
                    $q->orWhere('id', $assignment);
                }
            })
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
            ], 404);
        }

        if ($user instanceof User && $user->branch_id && $record->branch_id && $record->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this assignment.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment retrieved successfully.',
            'data' => [
                'assignment' => $this->transformAssignment($record, true),
            ],
        ]);
    }

    protected function transformAssignment(ClassAssignment $assignment, bool $includeMeta = false): array
    {
        $data = [
            'id' => $assignment->id,
            'uuid' => $assignment->uuid,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'assignment_type' => $assignment->assignment_type,
            'status' => $assignment->status,
            'assigned_date' => optional($assignment->assigned_date)->toDateString(),
            'due_date' => optional($assignment->due_date)->toDateString(),
            'due_time' => optional($assignment->due_time)->format('H:i'),
            'total_marks' => $assignment->total_marks,
            'attachments' => $assignment->attachments ?? [],
            'published_at' => optional($assignment->published_at)->toIso8601String(),
            'class_room' => $assignment->classRoom ? [
                'id' => $assignment->classRoom->id,
                'name' => $assignment->classRoom->name,
                'code' => $assignment->classRoom->code,
            ] : null,
            'subject' => $assignment->subject ? [
                'id' => $assignment->subject->id,
                'name' => $assignment->subject->name,
                'code' => $assignment->subject->code,
            ] : null,
            'branch' => $assignment->branch ? [
                'id' => $assignment->branch->id,
                'name' => $assignment->branch->name,
                'code' => $assignment->branch->code,
            ] : null,
            'teacher' => $assignment->teacher ? [
                'id' => $assignment->teacher->id,
                'name' => $assignment->teacher->name,
                'email' => $assignment->teacher->email,
            ] : null,
        ];

        if ($includeMeta) {
            $data['is_overdue'] = $assignment->due_date ? $assignment->due_date->isPast() : false;
        }

        return $data;
    }
}
