<?php

namespace App\Services;

use App\Models\ParentGuardian;
use App\Models\SmallGroup;
use App\Models\SmallGroupAttendance;
use App\Models\SmallGroupAttendanceChange;
use App\Models\SmallGroupMeeting;
use App\Models\SmallGroupMember;
use App\Models\SmallGroupSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SmallGroupService
{
    public function settings(int $businessId): SmallGroupSetting
    {
        return SmallGroupSetting::forBusiness($businessId);
    }

    public function transformGroup(SmallGroup $group, ?SmallGroupSetting $settings = null): array
    {
        $settings ??= $this->settings((int) $group->business_id);
        $memberCount = (int) ($group->members_count ?? $group->members()->count());
        $values = $group->custom_values ?? [];
        $custom = [];
        foreach ($settings->custom_fields ?? [] as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $custom[] = [
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'value' => $values[$key] ?? null,
            ];
        }

        return [
            'id' => $group->id,
            'uuid' => $group->uuid,
            'name' => $group->name,
            'location' => $group->location,
            'host_name' => $group->host_name,
            'host_phone' => $group->host_phone,
            'leader_name' => $group->leader_name,
            'leader_phone' => $group->leader_phone,
            'leader_user_id' => $group->leader_user_id,
            'capacity' => (int) $group->capacity,
            'member_count' => $memberCount,
            'available_spaces' => max(0, (int) $group->capacity - $memberCount),
            'custom_fields' => $custom,
            'custom_values' => $values,
        ];
    }

    /**
     * @param  array<int, int>  $studentIds
     */
    public function enrol(ParentGuardian $parent, SmallGroup $group, array $studentIds): array
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($studentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Select at least one of your children.',
            ]);
        }

        return DB::transaction(function () use ($parent, $group, $studentIds) {
            $locked = SmallGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            $owned = Student::query()
                ->where('parent_guardian_id', $parent->id)
                ->where('business_id', $locked->business_id)
                ->whereIn('id', $studentIds)
                ->pluck('id');

            if ($owned->count() !== $studentIds->count()) {
                throw ValidationException::withMessages([
                    'student_ids' => 'You can only enrol your own linked children.',
                ]);
            }

            $existing = SmallGroupMember::query()
                ->where('small_group_id', $locked->id)
                ->whereIn('student_id', $owned)
                ->pluck('student_id');
            $toAdd = $owned->diff($existing)->values();
            $current = SmallGroupMember::query()->where('small_group_id', $locked->id)->count();

            if ($current + $toAdd->count() > (int) $locked->capacity) {
                $spaces = max(0, (int) $locked->capacity - $current);
                throw ValidationException::withMessages([
                    'student_ids' => $spaces === 0
                        ? 'This group is full.'
                        : "Only {$spaces} space(s) left.",
                ]);
            }

            foreach ($toAdd as $studentId) {
                SmallGroupMember::create([
                    'small_group_id' => $locked->id,
                    'business_id' => $locked->business_id,
                    'student_id' => $studentId,
                    'enrolled_by_parent_id' => $parent->id,
                    'enrolled_at' => now(),
                ]);
            }

            return [
                'enrolled' => $toAdd->count(),
                'already_enrolled' => $existing->count(),
                'group' => $this->transformGroup($locked->fresh()->loadCount('members')),
            ];
        });
    }

    public function submitAttendance(ParentGuardian $parent, SmallGroupMeeting $meeting, array $studentIds): array
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($studentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Select the children who attended.',
            ]);
        }

        $meeting->loadMissing('group');
        $allowed = SmallGroupMember::query()
            ->where('small_group_id', $meeting->small_group_id)
            ->whereIn('student_id', Student::query()
                ->where('parent_guardian_id', $parent->id)
                ->where('business_id', $meeting->business_id)
                ->whereIn('id', $studentIds)
                ->pluck('id'))
            ->pluck('student_id');

        if ($allowed->count() !== $studentIds->count()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Attendance can only be submitted for your children enrolled in this group.',
            ]);
        }

        $saved = [];
        foreach ($allowed as $studentId) {
            try {
                $saved[] = $this->saveAttendanceSubmission($meeting, (int) $studentId, $parent);
            } catch (QueryException $exception) {
                $existing = SmallGroupAttendance::query()
                    ->where('small_group_meeting_id', $meeting->id)
                    ->where('student_id', $studentId)
                    ->first();
                if (! $existing) {
                    throw $exception;
                }
                $saved[] = $this->saveAttendanceSubmission($meeting, (int) $studentId, $parent);
            }
        }

        return $saved;
    }

    protected function saveAttendanceSubmission(SmallGroupMeeting $meeting, int $studentId, ParentGuardian $parent): SmallGroupAttendance
    {
        return DB::transaction(function () use ($meeting, $studentId, $parent) {
                $record = SmallGroupAttendance::query()
                    ->where('small_group_meeting_id', $meeting->id)
                    ->where('student_id', $studentId)
                    ->lockForUpdate()
                    ->first();

                $now = now();
                if ($record) {
                    $record->fill([
                        'status' => 'present',
                        'verification_status' => 'pending',
                        'verified_by_user_id' => null,
                        'verified_at' => null,
                        'last_changed_by_parent_id' => $parent->id,
                        'last_changed_by_user_id' => null,
                        'last_changed_at' => $now,
                    ])->save();
                    $this->logChange($record, null, $parent, 'updated', 'Parent updated attendance. Pending verification.');
                } else {
                    $record = SmallGroupAttendance::create([
                        'small_group_meeting_id' => $meeting->id,
                        'small_group_id' => $meeting->small_group_id,
                        'business_id' => $meeting->business_id,
                        'student_id' => $studentId,
                        'status' => 'present',
                        'verification_status' => 'pending',
                        'submitted_by_parent_id' => $parent->id,
                        'last_changed_by_parent_id' => $parent->id,
                        'last_changed_at' => $now,
                    ]);
                    $this->logChange($record, null, $parent, 'submitted', 'Parent submitted attendance. Pending verification.');
                }

                return $record->fresh(['student']);
            });
    }

    public function verify(User $actor, SmallGroupAttendance $record): SmallGroupAttendance
    {
        $this->assertCanVerify($actor, $record);
        $record->verification_status = 'verified';
        $record->verified_by_user_id = $actor->id;
        $record->verified_at = now();
        $record->last_changed_by_user_id = $actor->id;
        $record->last_changed_by_parent_id = null;
        $record->last_changed_at = now();
        $record->save();
        $this->logChange($record, $actor, null, 'verified', 'Attendance verified.');

        return $record->fresh();
    }

    public function correct(User $actor, SmallGroupAttendance $record, string $status): SmallGroupAttendance
    {
        $this->assertCanVerify($actor, $record);
        if (! in_array($status, ['present', 'removed'], true)) {
            throw ValidationException::withMessages(['status' => 'Choose attended or removed.']);
        }

        $record->status = $status;
        $record->verification_status = 'verified';
        $record->verified_by_user_id = $actor->id;
        $record->verified_at = now();
        $record->last_changed_by_user_id = $actor->id;
        $record->last_changed_by_parent_id = null;
        $record->last_changed_at = now();
        $record->save();
        $this->logChange($record, $actor, null, 'corrected', $status === 'present' ? 'Marked as attended.' : 'Attendance removed.');

        return $record->fresh();
    }

    public function userCanVerifyGroup(User $user, SmallGroup $group): bool
    {
        if ((int) $user->business_id !== (int) $group->business_id && ! $user->isAdmin()) {
            return false;
        }

        if ($user->canVerifySmallGroupAttendance()) {
            return true;
        }

        return (int) $group->leader_user_id === (int) $user->id;
    }

    /**
     * @return array{week_start: string, week_end: string, week_label: string, groups: array<int, array<string, mixed>>}
     */
    public function weeklyReport(int $businessId, Carbon $anchor, ?int $groupId = null, ?string $meetingDate = null, ?User $viewer = null): array
    {
        $start = $anchor->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $groups = SmallGroup::query()
            ->withCount('members')
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->when($groupId, fn ($query) => $query->whereKey($groupId))
            ->when($viewer && ! $viewer->canViewSmallGroups() && ! $viewer->canManageSmallGroups(), function ($query) use ($viewer) {
                $query->where('leader_user_id', $viewer->id);
            })
            ->orderBy('name')
            ->get();

        $meetings = SmallGroupMeeting::query()
            ->with(['attendances' => function ($query) {
                $query->where('status', 'present')->with('student:id,first_name,last_name');
            }])
            ->where('business_id', $businessId)
            ->whereIn('small_group_id', $groups->pluck('id'))
            ->when($meetingDate, function ($query) use ($meetingDate) {
                $query->whereDate('meeting_date', $meetingDate);
            }, function ($query) use ($start, $end) {
                $query->whereBetween('meeting_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->orderBy('meeting_date')
            ->get()
            ->groupBy('small_group_id');

        $rows = $groups->map(function (SmallGroup $group) use ($meetings) {
            $groupMeetings = $meetings->get($group->id, collect());

            return [
                'group' => $this->transformGroup($group),
                'state' => $groupMeetings->isEmpty() ? 'no_meeting' : 'met',
                'meetings' => $groupMeetings->map(function (SmallGroupMeeting $meeting) {
                    $attended = $meeting->attendances->where('status', 'present');

                    return [
                        'id' => $meeting->id,
                        'meeting_date' => optional($meeting->meeting_date)->toDateString(),
                        'location' => $meeting->location,
                        'attendance_count' => $attended->count(),
                        'state' => $attended->isEmpty() ? 'no_attendance' : 'recorded',
                        'attendees' => $attended->map(fn (SmallGroupAttendance $row) => [
                            'student_id' => $row->student_id,
                            'name' => $row->student?->full_name,
                            'verification_status' => $row->verification_status,
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'week_label' => $start->format('j M').' – '.$end->format('j M Y'),
            'groups' => $rows,
        ];
    }

    public function normalizeCustomFields(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $key = Str::slug($label, '_');
            if ($key === '' || isset($normalized[$key])) {
                continue;
            }
            $normalized[$key] = [
                'key' => $key,
                'label' => $label,
                'required' => (bool) ($field['required'] ?? false),
            ];
        }

        return array_values($normalized);
    }

    public function transformAttendance(SmallGroupAttendance $record): array
    {
        $record->loadMissing(['student', 'meeting.group', 'lastChangedByUser', 'lastChangedByParent', 'verifiedBy']);
        $changer = $record->lastChangedByUser?->name ?: $record->lastChangedByParent?->full_name;

        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'student_name' => $record->student?->full_name,
            'group_id' => $record->small_group_id,
            'group_name' => $record->meeting?->group?->name,
            'meeting_id' => $record->small_group_meeting_id,
            'meeting_date' => optional($record->meeting?->meeting_date)->toDateString(),
            'location' => $record->meeting?->location,
            'status' => $record->status,
            'status_label' => $record->status === 'present' ? 'Attended' : 'Removed',
            'verification_status' => $record->verification_status,
            'verification_label' => $record->verification_status === 'verified' ? 'Verified' : 'Pending verification',
            'changed_by' => $changer,
            'changed_at' => optional($record->last_changed_at)->toIso8601String(),
            'verified_by' => $record->verifiedBy?->name,
        ];
    }

    protected function assertCanVerify(User $actor, SmallGroupAttendance $record): void
    {
        $record->loadMissing('group');
        if (! $record->group || ! $this->userCanVerifyGroup($actor, $record->group)) {
            abort(403, 'You cannot verify attendance for this group.');
        }
    }

    protected function logChange(SmallGroupAttendance $record, ?User $user, ?ParentGuardian $parent, string $action, string $summary): void
    {
        SmallGroupAttendanceChange::create([
            'small_group_attendance_id' => $record->id,
            'actor_user_id' => $user?->id,
            'actor_parent_id' => $parent?->id,
            'action' => $action,
            'summary' => $summary,
        ]);
    }
}
