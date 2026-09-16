<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\PickupCode;
use App\Models\Student;
use Illuminate\Support\Carbon;

class PickupCodeService
{
    public function ensureForStudent(Student $student, int $businessId, ?int $markedBy = null): PickupCode
    {
        $today = Carbon::today(config('app.timezone', 'Africa/Nairobi'))->toDateString();

        $existing = PickupCode::query()
            ->where('student_id', $student->id)
            ->whereDate('code_date', $today)
            ->first();

        if ($existing) {
            if ((int) $existing->business_id !== (int) $businessId) {
                $existing->update(['business_id' => $businessId]);
            }

            return $existing;
        }

        return PickupCode::create([
            'business_id' => $businessId,
            'student_id' => $student->id,
            'code_date' => $today,
            'code' => $this->uniqueCode($businessId, $today),
            'expires_at' => Carbon::parse($today, config('app.timezone'))->endOfDay(),
        ]);
    }

    public function acceptForCheckIn(Student $student, string $code, int $businessId): PickupCode
    {
        $normalized = str_pad(preg_replace('/\D/', '', $code) ?: $code, 4, '0', STR_PAD_LEFT);

        $pickup = PickupCode::query()
            ->where('business_id', $businessId)
            ->where('student_id', $student->id)
            ->whereDate('code_date', Carbon::today())
            ->where('code', $normalized)
            ->first();

        if (! $pickup) {
            abort(response()->json([
                'success' => false,
                'message' => 'That 4-digit code was not generated in the parent app today. Ask the parent to open Check-in and show you the code.',
            ], 422));
        }

        if ($pickup->used_at) {
            abort(response()->json([
                'success' => false,
                'message' => 'This pickup code has already been used.',
            ], 422));
        }

        return $pickup;
    }

    public function issueForAttendance(Attendance $attendance): PickupCode
    {
        $date = Carbon::parse($attendance->attendance_date)->toDateString();

        $existing = PickupCode::query()
            ->where('student_id', $attendance->student_id)
            ->whereDate('code_date', $date)
            ->first();

        if ($existing) {
            if (! $existing->attendance_id) {
                $existing->update(['attendance_id' => $attendance->id]);
            }

            return $existing;
        }

        return PickupCode::create([
            'business_id' => $attendance->business_id,
            'student_id' => $attendance->student_id,
            'attendance_id' => $attendance->id,
            'code_date' => $date,
            'code' => $this->uniqueCode((int) $attendance->business_id, $date),
            'expires_at' => Carbon::parse($date, config('app.timezone'))->endOfDay(),
        ]);
    }

    public function redeem(Student $student, string $code, int $businessId): PickupCode
    {
        $normalized = str_pad(preg_replace('/\D/', '', $code) ?: $code, 4, '0', STR_PAD_LEFT);

        $pickup = PickupCode::query()
            ->where('business_id', $businessId)
            ->where('student_id', $student->id)
            ->whereDate('code_date', Carbon::today())
            ->where('code', $normalized)
            ->first();

        if (! $pickup) {
            abort(response()->json([
                'success' => false,
                'message' => 'Pickup code does not match today’s check-in.',
            ], 422));
        }

        if (! $pickup->isValid()) {
            abort(response()->json([
                'success' => false,
                'message' => 'This pickup code has already been used or has expired.',
            ], 422));
        }

        $pickup->update(['used_at' => now()]);

        return $pickup;
    }

    protected function uniqueCode(int $businessId, string $date): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (
            PickupCode::query()
                ->where('business_id', $businessId)
                ->whereDate('code_date', $date)
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }
}
