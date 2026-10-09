<?php

namespace App\Livewire\SchoolManagement;

use App\Models\SmallGroup;
use App\Models\SmallGroupAttendance;
use App\Models\SmallGroupMeeting;
use App\Models\SmallGroupMember;
use App\Models\User;
use App\Services\SmallGroupService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class SmallGroupManagement extends Component
{
    public string $section = 'groups';

    public string $search = '';

    public string $location = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $groupLocation = '';

    public string $hostName = '';

    public string $hostPhone = '';

    public string $leaderName = '';

    public string $leaderPhone = '';

    public $leaderUserId = null;

    public $capacity = 12;

    public array $customValues = [];

    public ?int $memberGroupId = null;

    public ?int $reviewMeetingId = null;

    public $meetingGroupId = null;

    public string $meetingDate = '';

    public string $meetingLocation = '';

    public string $reportWeek = '';

    public $reportGroupId = null;

    public string $reportDate = '';

    public string $moduleLabel = 'Small Groups';

    public string $singularLabel = 'Small group';

    public array $customFields = [];

    public function mount(): void
    {
        $user = auth()->user();
        $business = $user?->business;
        abort_unless($business && $business->isChurch(), 403);
        abort_unless($user->canViewSmallGroups() || $this->leadsAGroup($user), 403);

        $settings = app(SmallGroupService::class)->settings((int) $business->id);
        $this->moduleLabel = $settings->module_label;
        $this->singularLabel = $settings->singular_label;
        $this->customFields = collect($settings->custom_fields ?? [])->map(fn ($field) => [
            'label' => $field['label'] ?? '',
            'required' => (bool) ($field['required'] ?? false),
        ])->values()->all();
        $this->reportWeek = Carbon::now($business->timezoneName())->toDateString();
        $this->meetingDate = $this->reportWeek;
    }

    public function saveSettings(): void
    {
        $this->authorizeManage();
        $this->validate([
            'moduleLabel' => 'required|string|max:80',
            'singularLabel' => 'required|string|max:80',
        ]);

        $settings = app(SmallGroupService::class)->settings((int) auth()->user()->business_id);
        $settings->update([
            'module_label' => trim($this->moduleLabel),
            'singular_label' => trim($this->singularLabel),
            'custom_fields' => app(SmallGroupService::class)->normalizeCustomFields($this->customFields),
        ]);
        $this->customFields = collect($settings->fresh()->custom_fields ?? [])->map(fn ($field) => [
            'label' => $field['label'],
            'required' => (bool) $field['required'],
        ])->all();
        session()->flash('success', 'Small group settings saved.');
    }

    public function addCustomField(): void
    {
        $this->customFields[] = ['label' => '', 'required' => false];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function editGroup(int $groupId): void
    {
        $group = $this->findGroup($groupId);
        $this->editingId = $group->id;
        $this->name = $group->name;
        $this->groupLocation = $group->location;
        $this->hostName = $group->host_name;
        $this->hostPhone = $group->host_phone;
        $this->leaderName = $group->leader_name;
        $this->leaderPhone = $group->leader_phone;
        $this->leaderUserId = $group->leader_user_id;
        $this->capacity = $group->capacity;
        $this->customValues = $group->custom_values ?? [];
        $this->section = 'groups';
    }

    public function resetGroupForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->groupLocation = '';
        $this->hostName = '';
        $this->hostPhone = '';
        $this->leaderName = '';
        $this->leaderPhone = '';
        $this->leaderUserId = null;
        $this->capacity = 12;
        $this->customValues = [];
    }

    public function saveGroup(): void
    {
        $this->authorizeManage();
        $settings = app(SmallGroupService::class)->settings((int) auth()->user()->business_id);
        $rules = [
            'name' => 'required|string|max:255',
            'groupLocation' => 'required|string|max:255',
            'hostName' => 'required|string|max:255',
            'hostPhone' => 'required|string|max:40',
            'leaderName' => 'required|string|max:255',
            'leaderPhone' => 'required|string|max:40',
            'leaderUserId' => 'nullable|integer',
            'capacity' => 'required|integer|min:1|max:500',
        ];
        foreach ($settings->custom_fields ?? [] as $field) {
            if (! empty($field['required'])) {
                $rules['customValues.'.$field['key']] = 'required|string|max:255';
            }
        }
        $this->validate($rules);

        $payload = [
            'business_id' => auth()->user()->business_id,
            'name' => trim($this->name),
            'location' => trim($this->groupLocation),
            'host_name' => trim($this->hostName),
            'host_phone' => trim($this->hostPhone),
            'leader_name' => trim($this->leaderName),
            'leader_phone' => trim($this->leaderPhone),
            'leader_user_id' => $this->leaderUserId ?: null,
            'capacity' => (int) $this->capacity,
            'custom_values' => $this->customValues,
            'status' => 'active',
        ];

        if ($this->editingId) {
            $this->findGroup($this->editingId)->update($payload);
            session()->flash('success', 'Group updated.');
        } else {
            SmallGroup::create($payload);
            session()->flash('success', 'Group created.');
        }
        $this->resetGroupForm();
    }

    public function viewMembers(int $groupId): void
    {
        $this->memberGroupId = $this->findGroup($groupId)->id;
        $this->section = 'members';
    }

    public function removeMember(int $memberId): void
    {
        $this->authorizeManage();
        $member = SmallGroupMember::query()
            ->where('business_id', auth()->user()->business_id)
            ->whereKey($memberId)
            ->firstOrFail();
        $member->delete();
        session()->flash('success', 'Member removed. A space is available again.');
    }

    public function saveMeeting(): void
    {
        $this->authorizeManage();
        $this->validate([
            'meetingGroupId' => 'required|integer',
            'meetingDate' => 'required|date',
            'meetingLocation' => 'required|string|max:255',
        ]);
        $group = $this->findGroup((int) $this->meetingGroupId);
        SmallGroupMeeting::create([
            'small_group_id' => $group->id,
            'business_id' => $group->business_id,
            'meeting_date' => $this->meetingDate,
            'location' => trim($this->meetingLocation),
            'created_by_user_id' => auth()->id(),
        ]);
        $this->meetingLocation = '';
        session()->flash('success', 'Meeting recorded.');
        $this->section = 'meetings';
    }

    public function reviewMeeting(int $meetingId): void
    {
        $meeting = SmallGroupMeeting::query()
            ->where('business_id', auth()->user()->business_id)
            ->whereKey($meetingId)
            ->firstOrFail();
        $this->reviewMeetingId = $meeting->id;
        $this->section = 'meetings';
    }

    public function verifyAttendance(int $attendanceId): void
    {
        $record = $this->findAttendance($attendanceId);
        app(SmallGroupService::class)->verify(auth()->user(), $record);
        session()->flash('success', 'Attendance verified.');
    }

    public function correctAttendance(int $attendanceId, string $status): void
    {
        $record = $this->findAttendance($attendanceId);
        app(SmallGroupService::class)->correct(auth()->user(), $record, $status);
        session()->flash('success', 'Attendance corrected.');
    }

    public function markAttended(int $meetingId, int $studentId): void
    {
        $meeting = SmallGroupMeeting::query()
            ->where('business_id', auth()->user()->business_id)
            ->whereKey($meetingId)
            ->firstOrFail();
        abort_unless(app(SmallGroupService::class)->userCanVerifyGroup(auth()->user(), $meeting->group), 403);

        $record = SmallGroupAttendance::query()->firstOrNew([
            'small_group_meeting_id' => $meeting->id,
            'student_id' => $studentId,
        ]);
        if (! $record->exists) {
            $record->fill([
                'small_group_id' => $meeting->small_group_id,
                'business_id' => $meeting->business_id,
                'status' => 'present',
                'verification_status' => 'pending',
                'submitted_by_parent_id' => null,
            ]);
            $record->save();
        }
        app(SmallGroupService::class)->correct(auth()->user(), $record->fresh(), 'present');
        session()->flash('success', 'Attendance saved.');
    }

    public function render()
    {
        $user = auth()->user();
        $businessId = (int) $user->business_id;
        $service = app(SmallGroupService::class);
        $settings = $service->settings($businessId);
        $leaderOnly = ! $user->canViewSmallGroups() && ! $user->canManageSmallGroups();

        $groups = SmallGroup::query()
            ->withCount('members')
            ->where('business_id', $businessId)
            ->when($leaderOnly, fn ($query) => $query->where('leader_user_id', $user->id))
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->location !== '', fn ($query) => $query->where('location', $this->location))
            ->orderBy('name')
            ->get();

        $locations = SmallGroup::query()
            ->where('business_id', $businessId)
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $members = collect();
        if ($this->memberGroupId) {
            $members = SmallGroupMember::query()
                ->with('student.parentGuardian')
                ->where('small_group_id', $this->memberGroupId)
                ->where('business_id', $businessId)
                ->orderBy('enrolled_at')
                ->get();
        }

        $meetings = SmallGroupMeeting::query()
            ->with('group')
            ->withCount(['attendances as present_count' => fn ($query) => $query->where('status', 'present')])
            ->where('business_id', $businessId)
            ->when($leaderOnly, fn ($query) => $query->whereHas('group', fn ($group) => $group->where('leader_user_id', $user->id)))
            ->orderByDesc('meeting_date')
            ->limit(40)
            ->get();

        $review = null;
        if ($this->reviewMeetingId) {
            $meeting = SmallGroupMeeting::query()->with('group')->where('business_id', $businessId)->whereKey($this->reviewMeetingId)->first();
            if ($meeting) {
                $roster = SmallGroupMember::query()->with('student')->where('small_group_id', $meeting->small_group_id)->get();
                $attendance = SmallGroupAttendance::query()
                    ->with(['lastChangedByUser', 'lastChangedByParent', 'verifiedBy'])
                    ->where('small_group_meeting_id', $meeting->id)
                    ->get()
                    ->keyBy('student_id');
                $review = compact('meeting', 'roster', 'attendance');
            }
        }

        $report = null;
        if ($this->section === 'report') {
            $report = $service->weeklyReport(
                $businessId,
                Carbon::parse($this->reportWeek ?: now()->toDateString()),
                $this->reportGroupId ? (int) $this->reportGroupId : null,
                $this->reportDate !== '' ? $this->reportDate : null,
                $user
            );
        }

        return view('livewire.school-management.small-group-management', [
            'groups' => $groups,
            'locations' => $locations,
            'settings' => $settings,
            'labels' => $settings->labels(),
            'canManage' => $user->canManageSmallGroups(),
            'staff' => User::query()->where('business_id', $businessId)->orderBy('name')->get(['id', 'name']),
            'members' => $members,
            'memberGroup' => $this->memberGroupId ? $groups->firstWhere('id', $this->memberGroupId) ?? SmallGroup::find($this->memberGroupId) : null,
            'meetings' => $meetings,
            'review' => $review,
            'report' => $report,
            'allGroups' => SmallGroup::query()->where('business_id', $businessId)->when($leaderOnly, fn ($query) => $query->where('leader_user_id', $user->id))->orderBy('name')->get(['id', 'name', 'location']),
        ]);
    }

    protected function findGroup(int $groupId): SmallGroup
    {
        return SmallGroup::query()
            ->where('business_id', auth()->user()->business_id)
            ->whereKey($groupId)
            ->firstOrFail();
    }

    protected function findAttendance(int $attendanceId): SmallGroupAttendance
    {
        return SmallGroupAttendance::query()
            ->where('business_id', auth()->user()->business_id)
            ->whereKey($attendanceId)
            ->firstOrFail();
    }

    public function canVerifyGroup(?SmallGroup $group): bool
    {
        if (! $group) {
            return false;
        }

        return app(SmallGroupService::class)->userCanVerifyGroup(auth()->user(), $group);
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->canManageSmallGroups(), 403);
    }

    protected function leadsAGroup(User $user): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('small_groups')) {
            return false;
        }

        return SmallGroup::query()->where('leader_user_id', $user->id)->exists();
    }
}
