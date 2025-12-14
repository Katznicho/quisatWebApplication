<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\ClassAssignment;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssignmentSubmissionController extends Controller
{
    public function store(Request $request, $assignment)
    {
        try {
            $businessId = $request->get('business_id');
            $user = $request->get('authenticated_user');

            // Find the assignment
            $assignmentRecord = ClassAssignment::where('business_id', $businessId)
                ->where(function ($q) use ($assignment) {
                    $q->where('uuid', $assignment);
                    if (is_numeric($assignment)) {
                        $q->orWhere('id', $assignment);
                    }
                })
                ->first();

            if (!$assignmentRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found.',
                ], 404);
            }

            // Get student - handle both parent and student submissions
            $studentId = $request->input('student_id');
            
            if (!$studentId) {
                // If user is a parent, get their first child's student_id
                if ($user instanceof ParentGuardian) {
                    $child = $user->students()->where('business_id', $businessId)->first();
                    if ($child) {
                        $studentId = $child->id;
                    }
                } elseif ($user instanceof User) {
                    // Try to find student by user email or other identifier
                    $student = Student::where('business_id', $businessId)
                        ->where('email', $user->email)
                        ->first();
                    $studentId = $student?->id;
                }
            }

            if (!$studentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found. Please provide student_id or ensure you have a linked student.',
                ], 404);
            }

            // Verify student belongs to the assignment's class
            $student = Student::find($studentId);
            if (!$student || $student->business_id !== $businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or does not belong to this business.',
                ], 404);
            }

            if ($student->class_room_id !== $assignmentRecord->class_room_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This assignment is not for the student\'s class.',
                ], 403);
            }

            $validated = $request->validate([
                'submission_file' => 'required|array',
                'submission_file.base64' => 'required|string',
                'submission_file.name' => 'required|string',
                'submission_file.mime_type' => 'required|string',
                'submission_file.size' => 'nullable|integer',
                'notes' => 'nullable|string',
            ]);

            // Handle file upload
            $base64 = $validated['submission_file']['base64'];
            $mimeType = $validated['submission_file']['mime_type'];
            $fileName = $validated['submission_file']['name'];
            $fileSize = $validated['submission_file']['size'] ?? 0;

            // Decode base64 and store file
            $fileContent = base64_decode($base64);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin';
            $storedFileName = uniqid() . '_' . time() . '.' . $extension;
            $path = 'assignments/submissions/' . $storedFileName;

            // Store file
            Storage::disk('public')->put($path, $fileContent);

            // Create or update submission
            $submission = AssignmentSubmission::updateOrCreate(
                [
                    'assignment_id' => $assignmentRecord->id,
                    'student_id' => $studentId,
                ],
                [
                    'submission_file_path' => $path,
                    'submission_file_name' => $fileName,
                    'submission_file_size' => $fileSize ?: strlen($fileContent),
                    'submission_mime_type' => $mimeType,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]
            );

            $submission->load(['student:id,first_name,last_name,student_id', 'assignment:id,title']);

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully.',
                'data' => [
                    'submission' => $this->transformSubmission($submission),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error submitting assignment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $assignment, $submission = null)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $assignmentRecord = ClassAssignment::where('business_id', $businessId)
            ->where(function ($q) use ($assignment) {
                $q->where('uuid', $assignment);
                if (is_numeric($assignment)) {
                    $q->orWhere('id', $assignment);
                }
            })
            ->first();

        if (!$assignmentRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
            ], 404);
        }

        // If submission ID provided, get specific submission
        if ($submission) {
            $submissionRecord = AssignmentSubmission::where('assignment_id', $assignmentRecord->id)
                ->where(function ($q) use ($submission) {
                    if (is_numeric($submission)) {
                        $q->where('id', $submission);
                    }
                })
                ->with(['student:id,first_name,last_name,student_id', 'assignment:id,title'])
                ->first();

            if (!$submissionRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Submission retrieved successfully.',
                'data' => [
                    'submission' => $this->transformSubmission($submissionRecord),
                ],
            ]);
        }

        // Get all submissions for this assignment
        $submissions = AssignmentSubmission::where('assignment_id', $assignmentRecord->id)
            ->with(['student:id,first_name,last_name,student_id', 'assignment:id,title'])
            ->get()
            ->map(fn ($sub) => $this->transformSubmission($sub));

        return response()->json([
            'success' => true,
            'message' => 'Submissions retrieved successfully.',
            'data' => [
                'submissions' => $submissions,
            ],
        ]);
    }

    protected function transformSubmission(AssignmentSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'student_id' => $submission->student_id,
            'submission_file_name' => $submission->submission_file_name,
            'submission_file_url' => $submission->submission_file_path ? asset('storage/' . $submission->submission_file_path) : null,
            'submission_file_size' => $submission->submission_file_size,
            'submission_mime_type' => $submission->submission_mime_type,
            'notes' => $submission->notes,
            'status' => $submission->status,
            'marks_obtained' => $submission->marks_obtained,
            'feedback' => $submission->feedback,
            'submitted_at' => optional($submission->submitted_at)->toIso8601String(),
            'graded_at' => optional($submission->graded_at)->toIso8601String(),
            'student' => $submission->student ? [
                'id' => $submission->student->id,
                'name' => $submission->student->first_name . ' ' . $submission->student->last_name,
                'student_id' => $submission->student->student_id,
            ] : null,
        ];
    }
}
