<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
