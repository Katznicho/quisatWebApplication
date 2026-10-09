<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\StaffAttendanceCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StaffAttendanceService
{
    public function assertCanRecord(User $actor, User $subject): ?string
    {
        if ((int) $actor->business_id !== (int) $subject->business_id && ! $actor->isAdmin()) {
            return 'You can only record attendance in your organisation.';
        }

        if ((int) $actor->id === (int) $subject->id) {
            return null;
        }

        if (! $actor->canRecordStaffAttendanceForOthers()) {
            return 'You can only record your own attendance.';
        }

        if (! $actor->isAdmin() && ! $actor->isBusinessAdmin() && $actor->branch_id && (int) $actor->branch_id !== (int) $subject->branch_id) {
            return 'You can only record attendance for staff in your branch.';
        }

        return null;
    }

    /**
     * @return array{saved: bool, status: int, message: string, record: ?StaffAttendance}
     */
    public function checkIn(User $actor, User $subject): array
    {
        $denied = $this->assertCanRecord($actor, $subject);
        if ($denied) {
            return ['saved' => false, 'status' => 403, 'message' => $denied, 'record' => null];
        }

        return DB::transaction(function () use ($actor, $subject) {
            User::query()->whereKey($subject->id)->lockForUpdate()->first();

            $open = $this->openSession($subject);
            if ($open) {
                return [
                    'saved' => false,
                    'status' => 409,
                    'message' => 'Already checked in.',
                    'record' => $open,
                ];
            }

            $timezone = $this->timezoneFor($subject);
            $record = StaffAttendance::create([
                'business_id' => $subject->business_id,
                'branch_id' => $subject->branch_id,
                'user_id' => $subject->id,
                'recorded_by_user_id' => $actor->id,
                'attendance_date' => Carbon::now($timezone)->toDateString(),
                'checked_in_at' => Carbon::now('UTC'),
            ]);

            return [
                'saved' => true,
                'status' => 200,
                'message' => 'Checked in.',
                'record' => $record,
            ];
        });
    }

    /**
     * @return array{saved: bool, status: int, message: string, record: ?StaffAttendance}
     */
    public function checkOut(User $actor, User $subject): array
    {
        $denied = $this->assertCanRecord($actor, $subject);
        if ($denied) {
            return ['saved' => false, 'status' => 403, 'message' => $denied, 'record' => null];
        }

        return DB::transaction(function () use ($actor, $subject) {
            User::query()->whereKey($subject->id)->lockForUpdate()->first();

            $open = StaffAttendance::query()
                ->where('user_id', $subject->id)
                ->where('business_id', $subject->business_id)
                ->whereNull('checked_out_at')
                ->lockForUpdate()
                ->latest('checked_in_at')
                ->first();

            if (! $open) {
                return [
                    'saved' => false,
                    'status' => 422,
                    'message' => 'Check in before checking out.',
                    'record' => null,
                ];
            }

            $open->checked_out_at = Carbon::now('UTC');
            $open->checked_out_by_user_id = $actor->id;
            $open->save();

            return [
                'saved' => true,
                'status' => 200,
                'message' => 'Checked out.',
                'record' => $open->fresh(),
            ];
        });
    }

    public function correct(User $actor, StaffAttendance $record, Carbon $checkedInAt, ?Carbon $checkedOutAt, string $reason): StaffAttendance
    {
        return DB::transaction(function () use ($actor, $record, $checkedInAt, $checkedOutAt, $reason) {
            $locked = StaffAttendance::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            StaffAttendanceCorrection::create([
                'staff_attendance_id' => $locked->id,
                'corrected_by_user_id' => $actor->id,
                'reason' => $reason,
                'original_checked_in_at' => $locked->checked_in_at,
                'original_checked_out_at' => $locked->checked_out_at,
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => $checkedOutAt,
            ]);

            $timezone = $locked->business?->timezoneName() ?? config('app.timezone', 'Africa/Nairobi');
            $locked->checked_in_at = $checkedInAt;
            $locked->checked_out_at = $checkedOutAt;
            $locked->attendance_date = $checkedInAt->copy()->timezone($timezone)->toDateString();
            $locked->save();

            return $locked->fresh();
        });
    }

    public function todayPayload(User $subject): array
    {
        $timezone = $this->timezoneFor($subject);
        $today = Carbon::now($timezone)->toDateString();
        $open = $this->openSession($subject);
        $completed = null;

        if (! $open) {
            $completed = StaffAttendance::query()
                ->where('user_id', $subject->id)
                ->where('business_id', $subject->business_id)
                ->whereDate('attendance_date', $today)
                ->whereNotNull('checked_out_at')
                ->latest('checked_out_at')
                ->first();
        }

        $record = $open ?: $completed;
        $status = 'not_checked_in';
        if ($open) {
            $status = 'checked_in';
        } elseif ($completed) {
            $status = 'checked_out';
        }

        return [
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'arrival' => $record ? $this->formatTime($record->checked_in_at, $timezone) : null,
            'departure' => $record && $record->checked_out_at ? $this->formatTime($record->checked_out_at, $timezone) : null,
            'total' => $record && $record->checked_out_at ? $this->totalLabel($record->checked_in_at, $record->checked_out_at) : null,
            'attendance' => $record ? $this->transform($record) : null,
        ];
    }

    public function transform(StaffAttendance $record): array
    {
        $record->loadMissing(['business', 'branch', 'user', 'recordedBy', 'checkedOutBy']);
        $timezone = $record->business?->timezoneName() ?? config('app.timezone', 'Africa/Nairobi');

        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'attendance_date' => optional($record->attendance_date)->toDateString(),
            'status' => $record->checked_out_at ? 'checked_out' : 'checked_in',
            'status_label' => $record->checked_out_at ? 'Checked out' : 'Checked in',
            'arrival' => $this->formatTime($record->checked_in_at, $timezone),
            'departure' => $record->checked_out_at ? $this->formatTime($record->checked_out_at, $timezone) : null,
            'total' => $record->checked_out_at ? $this->totalLabel($record->checked_in_at, $record->checked_out_at) : null,
            'staff' => [
                'id' => $record->user_id,
                'name' => $record->user?->name,
            ],
            'organisation' => $record->business?->name,
            'branch' => $record->branch?->name,
            'recorded_by' => $record->recordedBy?->name,
            'checked_out_by' => $record->checkedOutBy?->name,
        ];
    }

    public function adminQuery(User $actor): Builder
    {
        $query = StaffAttendance::query()->with(['user:id,name', 'branch:id,name', 'business:id,name,timezone', 'recordedBy:id,name']);

        if (! $actor->isAdmin()) {
            $query->where('business_id', $actor->business_id);
        }

        return $query;
    }

    public function timezoneFor(User $subject): string
    {
        $subject->loadMissing('business');

        return $subject->business?->timezoneName() ?? (string) config('app.timezone', 'Africa/Nairobi');
    }

    public function formatTime(?Carbon $value, string $timezone): ?string
    {
        if (! $value) {
            return null;
        }

        return $value->copy()->timezone($timezone)->format('g:i A');
    }

    public function totalLabel(?Carbon $start, ?Carbon $end): ?string
    {
        if (! $start || ! $end) {
            return null;
        }

        $minutes = (int) $start->diffInMinutes($end);
        $hours = intdiv($minutes, 60);
        $remain = $minutes % 60;

        if ($hours === 0) {
            return $remain.'m';
        }

        return $hours.'h '.$remain.'m';
    }

    protected function openSession(User $subject): ?StaffAttendance
    {
        return StaffAttendance::query()
            ->where('user_id', $subject->id)
            ->where('business_id', $subject->business_id)
            ->whereNull('checked_out_at')
            ->latest('checked_in_at')
            ->first();
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            default => 'Not checked in',
        };
    }
}
