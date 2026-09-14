<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ParentGuardian;
use App\Models\PickupCode;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\PickupCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        protected PickupCodeService $pickupCodes
    ) {}

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

        return response()->json([
            'success' => true,
            'message' => 'Attendance history loaded successfully.',
            'data' => $this->historyPayload($business->id, $student, $limit),
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

            $record = Attendance::firstOrNew([
                'business_id' => $business->id,
                'student_id' => $student->id,
                'class_room_id' => $student->class_room_id,
                'attendance_date' => Carbon::today(),
            ]);

            $record->status = 'present';
            $record->marked_by = $this->markedByUserId($user, $business->id);
            $record->remarks = $this->attendanceRemark('Checked in', $validated);
            if (! $record->check_in_time) {
                $record->check_in_time = Carbon::now()->format('H:i:s');
            }
            $record->check_out_time = null;
            $record->save();

            $pickup = $this->pickupCodes->issueForAttendance($record);
            if ($pickup->used_at) {
                $pickup->update(['used_at' => null]);
            }

            try {
                app(\App\Services\KidsChurchNotificationService::class)->notifyPickupCode($pickup);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Pickup code notification failed: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully.',
                'data' => [
                    'attendance' => $this->transformAttendance($record->fresh()),
                    'pickup_code' => $pickup->code,
                    'pickup_expires_at' => optional($pickup->expires_at)->toIso8601String(),
                    'student' => [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'allergies' => $student->allergies,
                        'medical_notes' => $student->medical_notes,
                        'dietary_restrictions' => $student->dietary_restrictions,
                        'emergency_contacts' => $student->emergency_contacts,
                        'has_medical_alert' => $student->hasMedicalAlert(),
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
                'pickup_code' => 'required|string|max:8',
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

            $record = Attendance::query()
                ->where('business_id', $business->id)
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', Carbon::today())
                ->first();

            if (! $record || ! $record->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'This child has not been checked in today.',
                ], 422);
            }

            if ($record->check_out_time) {
                return response()->json([
                    'success' => true,
                    'message' => 'Child is already checked out.',
                    'data' => [
                        'attendance' => $this->transformAttendance($record),
                    ],
                ]);
            }

            $this->pickupCodes->redeem($student, $validated['pickup_code'], $business->id);

            $record->update([
                'status' => 'present',
                'check_out_time' => Carbon::now()->format('H:i:s'),
                'marked_by' => $this->markedByUserId($user, $business->id) ?: $record->marked_by,
                'remarks' => $this->attendanceRemark('Checked out', $validated),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out recorded successfully.',
                'data' => [
                    'attendance' => $this->transformAttendance($record->fresh()),
                ],
            ]);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-out. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ensurePickupCodes(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can generate pickup codes automatically.',
            ], 403);
        }

        $students = $user->students()->get();

        $codes = $students->map(function (Student $student) use ($user) {
            $pickup = $this->pickupCodes->ensureForStudent(
                $student,
                (int) $student->business_id,
                $this->markedByUserId($user, (int) $student->business_id)
            );

            if ($pickup->wasRecentlyCreated) {
                try {
                    app(\App\Services\KidsChurchNotificationService::class)->notifyPickupCode($pickup);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Pickup code notification failed: '.$e->getMessage());
                }
            }

            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'code' => $pickup->code,
                'used' => (bool) $pickup->used_at,
                'expires_at' => optional($pickup->expires_at)->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Pickup codes are ready.',
            'data' => ['pickup_codes' => $codes],
        ]);
    }

    public function pickupCodes(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        $query = PickupCode::query()
            ->with(['student:id,first_name,last_name,class_room_id'])
            ->where('business_id', $business->id)
            ->whereDate('code_date', Carbon::today())
            ->latest('id');

        if ($user instanceof ParentGuardian) {
            $studentIds = Student::query()
                ->where('parent_guardian_id', $user->id)
                ->where('business_id', $business->id)
                ->pluck('id');
            $query->whereIn('student_id', $studentIds);
        } elseif (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $codes = $query->get()->map(function (PickupCode $code) {
            return [
                'student_id' => $code->student_id,
                'student_name' => $code->student?->full_name,
                'code' => $code->code,
                'used' => (bool) $code->used_at,
                'expires_at' => optional($code->expires_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => ['pickup_codes' => $codes],
        ]);
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

        return response()->json([
            'success' => true,
            'message' => 'Attendance history loaded successfully.',
            'data' => $this->historyPayload($business->id, $student, $limit),
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

    protected function historyPayload(int $businessId, Student $student, int $limit): array
    {
        $todayCode = PickupCode::query()
            ->where('student_id', $student->id)
            ->whereDate('code_date', Carbon::today())
            ->first();

        $attendanceRecords = Attendance::query()
            ->with('classRoom:id,name,code')
            ->where('business_id', $businessId)
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->limit($limit)
            ->get()
            ->map(fn (Attendance $attendance) => $this->transformAttendance($attendance));

        return [
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'class' => $student->classRoom?->name,
                'allergies' => $student->allergies,
                'medical_notes' => $student->medical_notes,
                'dietary_restrictions' => $student->dietary_restrictions,
                'emergency_contacts' => $student->emergency_contacts,
                'has_medical_alert' => $student->hasMedicalAlert(),
            ],
            'today_pickup_code' => $todayCode && ! $todayCode->used_at ? $todayCode->code : null,
            'attendance' => $attendanceRecords,
        ];
    }

    protected function transformAttendance(Attendance $attendance): array
    {
        $checkIn = $attendance->check_in_time;
        $checkOut = $attendance->check_out_time;

        return [
            'id' => $attendance->id,
            'attendance_date' => optional($attendance->attendance_date)->toDateString(),
            'status' => $attendance->status,
            'checked_out' => (bool) $checkOut,
            'check_in_time' => $checkIn ? Carbon::parse($checkIn)->format('H:i') : null,
            'check_out_time' => $checkOut ? Carbon::parse($checkOut)->format('H:i') : null,
            'class_room' => $attendance->classRoom?->name,
            'marked_by' => $attendance->marked_by,
            'remarks' => $attendance->remarks,
        ];
    }

    protected function attendanceRemark(string $action, array $validated): string
    {
        $name = $validated['parent_name'] ?? 'Parent/Guardian';
        $identifier = $validated['parent_identifier'] ?? '';

        return $identifier !== ''
            ? "{$action} by {$name} ({$identifier})"
            : "{$action} by {$name}";
    }

    protected function markedByUserId($user, int $businessId): ?int
    {
        if ($user instanceof ParentGuardian) {
            $parentUser = User::where('email', $user->email)
                ->where('business_id', $businessId)
                ->first();

            if (! $parentUser) {
                $parentUser = User::create([
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'business_id' => $businessId,
                    'status' => 'active',
                    'branch_id' => null,
                    'password' => '',
                ]);
            }

            return $parentUser->id;
        }

        if ($user instanceof User) {
            return $user->id;
        }

        return null;
    }
}
