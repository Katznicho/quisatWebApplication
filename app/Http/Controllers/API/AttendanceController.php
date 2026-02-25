<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function studentHistory(Request $request)
    {
        $business = $request->get('business');
        $studentId = $request->query('student_id');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'student_id query parameter is required.',
            ], 422);
        }

        $student = Student::where('business_id', $business->id)
            ->where('id', $studentId)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        $attendanceRecords = Attendance::query()
            ->with('classRoom:id,name,code')
            ->where('business_id', $business->id)
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->limit($limit)
            ->get()
            ->map(function (Attendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'attendance_date' => optional($attendance->attendance_date)->toDateString(),
                    'status' => $attendance->status,
                    'class_room' => $attendance->classRoom?->name,
                    'marked_by' => $attendance->marked_by,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Attendance history loaded successfully.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'class' => $student->classRoom?->name,
                ],
                'attendance' => $attendanceRecords,
            ],
        ]);
    }

    public function checkIn(Request $request)
    {
        try {
            $business = $request->get('business');
            $user = $request->get('authenticated_user');

            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'parent_name' => 'nullable|string|max:255',
                'parent_identifier' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in AttendanceController@checkIn: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing check-in.',
                'error' => $e->getMessage(),
            ], 500);
        }

        try {
            $student = Student::where('business_id', $business->id)
                ->where('id', $validated['student_id'])
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found.',
                ], 404);
            }

            // Get user ID for marked_by
            // If user is a ParentGuardian, find or create a User record
            $markedByUserId = null;
            if ($user instanceof \App\Models\ParentGuardian) {
                // Find or create a User record for the parent
                $parentUser = User::where('email', $user->email)
                    ->where('business_id', $business->id)
                    ->first();
                
                if (!$parentUser) {
                    // Create a user account for the parent if it doesn't exist
                    $parentUser = User::create([
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'business_id' => $business->id,
                        'status' => 'active',
                        'branch_id' => null,
                        'password' => '', // Empty password - parent uses ParentGuardian login
                    ]);
                }
                
                $markedByUserId = $parentUser->id;
            } elseif ($user instanceof \App\Models\User) {
                $markedByUserId = $user->id;
            }

            $record = Attendance::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'student_id' => $student->id,
                    'class_room_id' => $student->class_room_id,
                    'attendance_date' => Carbon::today(),
                ],
                [
                    'status' => 'present',
                    'marked_by' => $markedByUserId,
                    'remarks' => (!empty($validated['parent_identifier'] ?? null)) 
                        ? 'Checked in by ' . ($validated['parent_name'] ?? 'Parent/Guardian') . ' (' . ($validated['parent_identifier'] ?? '') . ')'
                        : ('Checked in by ' . ($validated['parent_name'] ?? 'Parent/Guardian')),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully.',
                'data' => [
                    'attendance' => [
                        'id' => $record->id,
                        'attendance_date' => optional($record->attendance_date)->toDateString(),
                        'status' => $record->status,
                        'marked_by' => $record->marked_by,
                        'remarks' => $record->remarks,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-in. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            $business = $request->get('business');
            $user = $request->get('authenticated_user');

            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'parent_name' => 'nullable|string|max:255',
                'parent_identifier' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in AttendanceController@checkOut: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing check-out.',
                'error' => $e->getMessage(),
            ], 500);
        }

        try {
            $student = Student::where('business_id', $business->id)
                ->where('id', $validated['student_id'])
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found.',
                ], 404);
            }

            // Get user ID for marked_by
            // If user is a ParentGuardian, find or create a User record
            $markedByUserId = null;
            if ($user instanceof \App\Models\ParentGuardian) {
                // Find or create a User record for the parent
                $parentUser = User::where('email', $user->email)
                    ->where('business_id', $business->id)
                    ->first();
                
                if (!$parentUser) {
                    // Create a user account for the parent if it doesn't exist
                    $parentUser = User::create([
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'business_id' => $business->id,
                        'status' => 'active',
                        'branch_id' => null,
                        'password' => '', // Empty password - parent uses ParentGuardian login
                    ]);
                }
                
                $markedByUserId = $parentUser->id;
            } elseif ($user instanceof \App\Models\User) {
                $markedByUserId = $user->id;
            }

            $record = Attendance::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'student_id' => $student->id,
                    'class_room_id' => $student->class_room_id,
                    'attendance_date' => Carbon::today(),
                ],
                [
                    'status' => 'excused',
                    'marked_by' => $markedByUserId,
                    'remarks' => (!empty($validated['parent_identifier'] ?? null)) 
                        ? 'Checked out by ' . ($validated['parent_name'] ?? 'Parent/Guardian') . ' (' . ($validated['parent_identifier'] ?? '') . ')'
                        : ('Checked out by ' . ($validated['parent_name'] ?? 'Parent/Guardian')),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-out recorded successfully.',
                'data' => [
                    'attendance' => [
                        'id' => $record->id,
                        'attendance_date' => optional($record->attendance_date)->toDateString(),
                        'status' => $record->status,
                        'marked_by' => $record->marked_by,
                        'remarks' => $record->remarks,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-out. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance history for a student (by route param). Same format as studentHistory.
     */
    public function studentAttendanceHistory(Request $request, Student $student)
    {
        $business = $request->get('business');

        if ($student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;

        $attendanceRecords = Attendance::query()
            ->with('classRoom:id,name,code')
            ->where('business_id', $business->id)
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->limit($limit)
            ->get()
            ->map(function (Attendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'attendance_date' => optional($attendance->attendance_date)->toDateString(),
                    'status' => $attendance->status,
                    'class_room' => $attendance->classRoom?->name,
                    'marked_by' => $attendance->marked_by,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Attendance history loaded successfully.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'class' => $student->classRoom?->name,
                ],
                'attendance' => $attendanceRecords,
            ],
        ]);
    }

    /**
     * Record daily attendance for a student (staff). Used from Student Character / progress flow.
     */
    public function recordForStudent(Request $request, Student $student)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if ($student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Only staff can record attendance for a student.',
            ], 403);
        }

        $validated = $request->validate([
            'record_date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused,sick',
            'remarks' => 'nullable|string|max:500',
        ]);

        $recordDate = Carbon::parse($validated['record_date'])->startOfDay();
        $term = Term::where('business_id', $business->id)->where('is_current_term', true)->first();

        $record = Attendance::updateOrCreate(
            [
                'business_id' => $business->id,
                'student_id' => $student->id,
                'class_room_id' => $student->class_room_id,
                'attendance_date' => $recordDate,
            ],
            [
                'term_id' => $term?->id,
                'status' => $validated['status'],
                'marked_by' => $user->id,
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully.',
            'data' => [
                'attendance' => [
                    'id' => $record->id,
                    'attendance_date' => $record->attendance_date->toDateString(),
                    'status' => $record->status,
                    'remarks' => $record->remarks,
                ],
            ],
        ]);
    }
}
