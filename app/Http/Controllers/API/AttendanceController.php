<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
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
        $business = $request->get('business');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_identifier' => 'nullable|string|max:255',
        ]);

        $student = Student::where('business_id', $business->id)
            ->where('id', $validated['student_id'])
            ->firstOrFail();

        $record = Attendance::updateOrCreate(
            [
                'business_id' => $business->id,
                'student_id' => $student->id,
                'class_room_id' => $student->class_room_id,
                'attendance_date' => Carbon::today(),
            ],
            [
                'status' => 'present',
                'marked_by' => $validated['parent_name'] ?? 'Parent/Guardian',
                'remarks' => $validated['parent_identifier'] ? 'Checked in by ' . $validated['parent_name'] . ' (' . $validated['parent_identifier'] . ')' : null,
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
    }

    public function checkOut(Request $request)
    {
        $business = $request->get('business');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_identifier' => 'nullable|string|max:255',
        ]);

        $student = Student::where('business_id', $business->id)
            ->where('id', $validated['student_id'])
            ->firstOrFail();

        $record = Attendance::updateOrCreate(
            [
                'business_id' => $business->id,
                'student_id' => $student->id,
                'class_room_id' => $student->class_room_id,
                'attendance_date' => Carbon::today(),
            ],
            [
                'status' => 'excused',
                'marked_by' => $validated['parent_name'] ?? 'Parent/Guardian',
                'remarks' => $validated['parent_identifier'] ? 'Checked out by ' . $validated['parent_name'] . ' (' . $validated['parent_identifier'] . ')' : null,
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
    }
}
