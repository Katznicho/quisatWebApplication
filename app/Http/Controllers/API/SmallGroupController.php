<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\SmallGroup;
use App\Models\SmallGroupAttendance;
use App\Models\SmallGroupMeeting;
use App\Models\SmallGroupMember;
use App\Models\SmallGroupVisitor;
use App\Models\User;
use App\Services\SmallGroupService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmallGroupController extends Controller
{
    public function __construct(
        protected SmallGroupService $groups
    ) {}

    public function index(Request $request)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId) {
            return $this->noChurch();
        }

        $settings = $this->groups->settings($businessId);
        $search = trim((string) $request->query('search', ''));
        $location = trim((string) $request->query('location', ''));

        $query = SmallGroup::query()
            ->withCount('members')
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }
        if ($location !== '') {
            $query->where('location', $location);
        }

        $locations = SmallGroup::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $settings->labels(),
                'locations' => $locations,
                'groups' => $query->get()->map(fn (SmallGroup $group) => $this->groups->transformGroup($group, $settings))->values(),
            ],
        ]);
    }

    public function show(Request $request, SmallGroup $smallGroup)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId || (int) $smallGroup->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'Group not found.'], 404);
        }

        $parent->studentsForChurchCheckIn($businessId);
        $children = $parent->students()
            ->where('business_id', $businessId)
            ->orderBy('first_name')
            ->get();
        $enrolled = SmallGroupMember::query()
            ->where('small_group_id', $smallGroup->id)
            ->whereIn('student_id', $children->pluck('id'))
            ->pluck('student_id')
            ->all();

        $smallGroup->loadCount('members');

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $this->groups->settings($businessId)->labels(),
                'group' => $this->groups->transformGroup($smallGroup),
                'children' => $children->map(fn ($student) => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'enrolled' => in_array($student->id, $enrolled, true),
                ])->values(),
            ],
        ]);
    }

    public function enrol(Request $request, SmallGroup $smallGroup)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId || (int) $smallGroup->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'Group not found.'], 404);
        }

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        try {
            $result = $this->groups->enrol($parent, $smallGroup, $validated['student_ids']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['enrolled'] > 0 ? 'Children enrolled.' : 'Those children are already enrolled.',
            'data' => $result,
        ]);
    }

    public function mine(Request $request)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId) {
            return $this->noChurch();
        }

        $parent->studentsForChurchCheckIn($businessId);
        $children = $parent->students()->where('business_id', $businessId)->orderBy('first_name')->get();
        $memberships = SmallGroupMember::query()
            ->with('group')
            ->where('business_id', $businessId)
            ->whereIn('student_id', $children->pluck('id'))
            ->get()
            ->groupBy('student_id');

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $this->groups->settings($businessId)->labels(),
                'children' => $children->map(function ($student) use ($memberships) {
                    $groups = ($memberships->get($student->id) ?? collect())
                        ->map(fn (SmallGroupMember $member) => $member->group ? [
                            'id' => $member->group->id,
                            'name' => $member->group->name,
                            'location' => $member->group->location,
                        ] : null)
                        ->filter()
                        ->values();

                    return [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'groups' => $groups,
                    ];
                })->values(),
            ],
        ]);
    }

    public function leaderBoard(Request $request)
    {
        $user = $this->staff($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $business = $request->get('business');
        $groups = $this->leaderGroups($user, (int) $business->id);
        $groupId = (int) $request->query('group_id');
        if ($groupId && ! $groups->contains('id', $groupId)) {
            return response()->json(['success' => false, 'message' => 'You can record attendance only for a group you lead.'], 403);
        }

        $selectedGroupId = $groupId ?: (int) ($groups->first()?->id ?? 0);
        $meetings = $selectedGroupId
            ? SmallGroupMeeting::query()
                ->with('group:id,name,location')
                ->where('business_id', $business->id)
                ->where('small_group_id', $selectedGroupId)
                ->orderByDesc('meeting_date')
                ->limit(40)
                ->get()
            : collect();

        $meetingId = (int) $request->query('meeting_id');
        $meeting = $meetingId
            ? $meetings->firstWhere('id', $meetingId)
            : $meetings->first();

        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups->map(fn (SmallGroup $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'location' => $group->location,
                ])->values(),
                'meetings' => $meetings->map(fn (SmallGroupMeeting $row) => $this->leaderMeetingSummary($row))->values(),
                'meeting' => $meeting ? $this->leaderMeetingDetail($meeting) : null,
            ],
        ]);
    }

    public function storeLeaderMeeting(Request $request)
    {
        $user = $this->staff($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'group_id' => 'required|integer',
            'meeting_date' => 'required|date',
            'location' => 'required|string|max:255',
        ]);

        $group = $this->findLeaderGroup($user, (int) $request->get('business')->id, (int) $validated['group_id']);
        $meeting = SmallGroupMeeting::query()->firstOrCreate(
            [
                'small_group_id' => $group->id,
                'meeting_date' => $validated['meeting_date'],
            ],
            [
                'business_id' => $group->business_id,
                'location' => trim($validated['location']),
                'created_by_user_id' => $user->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Meeting ready. Mark each member present or absent.',
            'data' => [
                'meeting' => $this->leaderMeetingDetail($meeting),
            ],
        ]);
    }

    public function storeLeaderAttendance(Request $request)
    {
        $user = $this->staff($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'meeting_id' => 'required|integer',
            'student_id' => 'required|integer',
            'status' => 'required|in:present,absent',
        ]);

        $meeting = $this->findLeaderMeeting($user, (int) $request->get('business')->id, (int) $validated['meeting_id']);
        $isMember = SmallGroupMember::query()
            ->where('small_group_id', $meeting->small_group_id)
            ->where('student_id', $validated['student_id'])
            ->exists();
        if (! $isMember) {
            return response()->json(['success' => false, 'message' => 'That child is not a member of this group.'], 422);
        }

        $record = SmallGroupAttendance::query()->firstOrNew([
            'small_group_meeting_id' => $meeting->id,
            'student_id' => $validated['student_id'],
        ]);
        if (! $record->exists) {
            $record->fill([
                'small_group_id' => $meeting->small_group_id,
                'business_id' => $meeting->business_id,
                'status' => $validated['status'],
                'verification_status' => 'pending',
            ]);
            $record->save();
        }

        $this->groups->correct($user, $record->fresh(), $validated['status']);

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'present' ? 'Marked present.' : 'Marked absent.',
            'data' => [
                'meeting' => $this->leaderMeetingDetail($meeting->fresh()),
            ],
        ]);
    }

    public function storeLeaderVisitor(Request $request)
    {
        $user = $this->staff($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'meeting_id' => 'required|integer',
            'visitor_name' => 'required|string|max:120',
            'guardian_name' => 'nullable|string|max:120',
            'contact' => 'nullable|string|max:40',
        ]);

        $meeting = $this->findLeaderMeeting($user, (int) $request->get('business')->id, (int) $validated['meeting_id']);
        SmallGroupVisitor::query()->create([
            'small_group_meeting_id' => $meeting->id,
            'small_group_id' => $meeting->small_group_id,
            'business_id' => $meeting->business_id,
            'visitor_name' => trim($validated['visitor_name']),
            'guardian_name' => trim((string) ($validated['guardian_name'] ?? '')) ?: null,
            'contact' => trim((string) ($validated['contact'] ?? '')) ?: null,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor recorded. They are not enrolled in the group.',
            'data' => [
                'meeting' => $this->leaderMeetingDetail($meeting->fresh()),
            ],
        ]);
    }

    public function meetings(Request $request)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId) {
            return $this->noChurch();
        }

        $groupId = (int) $request->query('group_id');
        $childIds = $parent->students()->where('business_id', $businessId)->pluck('id');
        $groupIds = SmallGroupMember::query()
            ->where('business_id', $businessId)
            ->whereIn('student_id', $childIds)
            ->pluck('small_group_id')
            ->unique();

        if ($groupId && ! $groupIds->contains($groupId)) {
            return response()->json(['success' => false, 'message' => 'Your children are not enrolled in that group.'], 403);
        }

        $meetings = SmallGroupMeeting::query()
            ->with('group:id,name,location')
            ->where('business_id', $businessId)
            ->whereIn('small_group_id', $groupId ? [$groupId] : $groupIds)
            ->orderByDesc('meeting_date')
            ->limit(40)
            ->get()
            ->map(fn (SmallGroupMeeting $meeting) => [
                'id' => $meeting->id,
                'group_id' => $meeting->small_group_id,
                'group_name' => $meeting->group?->name,
                'meeting_date' => optional($meeting->meeting_date)->toDateString(),
                'location' => $meeting->location,
            ]);

        $enrolledChildren = SmallGroupMember::query()
            ->with('student:id,first_name,last_name')
            ->where('business_id', $businessId)
            ->whereIn('student_id', $childIds)
            ->when($groupId, fn ($query) => $query->where('small_group_id', $groupId))
            ->get()
            ->map(fn (SmallGroupMember $member) => [
                'id' => $member->student_id,
                'full_name' => $member->student?->full_name,
                'group_id' => $member->small_group_id,
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'meetings' => $meetings,
                'children' => $enrolledChildren,
            ],
        ]);
    }

    public function storeAttendance(Request $request)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId) {
            return $this->noChurch();
        }

        $validated = $request->validate([
            'meeting_id' => 'required|integer',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        $meeting = SmallGroupMeeting::query()
            ->where('business_id', $businessId)
            ->whereKey($validated['meeting_id'])
            ->first();

        if (! $meeting) {
            return response()->json([
                'success' => false,
                'message' => 'Choose a meeting the church has already recorded.',
            ], 422);
        }

        try {
            $saved = $this->groups->submitAttendance($parent, $meeting, $validated['student_ids']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance submitted and waiting for verification.',
            'data' => [
                'attendance' => collect($saved)->map(fn (SmallGroupAttendance $row) => $this->groups->transformAttendance($row))->values(),
            ],
        ]);
    }

    public function history(Request $request)
    {
        $parent = $this->parent($request);
        if ($parent instanceof \Illuminate\Http\JsonResponse) {
            return $parent;
        }

        $businessId = $this->churchId($request, $parent);
        if (! $businessId) {
            return $this->noChurch();
        }

        $childIds = $parent->students()->where('business_id', $businessId)->pluck('id');
        $rows = SmallGroupAttendance::query()
            ->with(['student', 'meeting.group', 'lastChangedByUser', 'lastChangedByParent', 'verifiedBy'])
            ->where('business_id', $businessId)
            ->whereIn('student_id', $childIds)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn (SmallGroupAttendance $row) => $this->groups->transformAttendance($row));

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $rows,
            ],
        ]);
    }

    protected function staff(Request $request): User|\Illuminate\Http\JsonResponse
    {
        $user = $request->get('authenticated_user');
        $business = $request->get('business');
        if (! $user instanceof User || ! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Only the assigned group leader can record this meeting.',
            ], 403);
        }

        return $user;
    }

    protected function leaderGroups(User $user, int $businessId)
    {
        return SmallGroup::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->when(
                ! $user->canManageSmallGroups() && ! $user->isAdmin() && ! $user->isBusinessAdmin(),
                fn ($query) => $query->where('leader_user_id', $user->id)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'leader_user_id', 'business_id']);
    }

    protected function findLeaderGroup(User $user, int $businessId, int $groupId): SmallGroup
    {
        $group = $this->leaderGroups($user, $businessId)->firstWhere('id', $groupId);
        abort_unless($group, 403);

        return $group;
    }

    protected function findLeaderMeeting(User $user, int $businessId, int $meetingId): SmallGroupMeeting
    {
        $meeting = SmallGroupMeeting::query()
            ->where('business_id', $businessId)
            ->whereKey($meetingId)
            ->firstOrFail();
        $this->findLeaderGroup($user, $businessId, (int) $meeting->small_group_id);

        return $meeting;
    }

    protected function leaderMeetingSummary(SmallGroupMeeting $meeting): array
    {
        return [
            'id' => $meeting->id,
            'group_id' => $meeting->small_group_id,
            'group_name' => $meeting->group?->name,
            'meeting_date' => optional($meeting->meeting_date)->toDateString(),
            'location' => $meeting->location,
        ];
    }

    protected function leaderMeetingDetail(SmallGroupMeeting $meeting): array
    {
        $members = SmallGroupMember::query()
            ->with('student:id,first_name,last_name')
            ->where('small_group_id', $meeting->small_group_id)
            ->get();
        $attendance = SmallGroupAttendance::query()
            ->where('small_group_meeting_id', $meeting->id)
            ->get()
            ->keyBy('student_id');
        $visitors = SmallGroupVisitor::query()
            ->where('small_group_meeting_id', $meeting->id)
            ->orderBy('visitor_name')
            ->get();
        $membersPresent = $attendance->where('status', 'present')->count();
        $membersAbsent = $attendance->where('status', 'absent')->count();

        return [
            ...$this->leaderMeetingSummary($meeting->loadMissing('group:id,name,location')),
            'members' => $members->map(function (SmallGroupMember $member) use ($attendance) {
                $row = $attendance->get($member->student_id);

                return [
                    'id' => $member->student_id,
                    'full_name' => $member->student?->full_name,
                    'status' => $row?->status ?: 'unmarked',
                ];
            })->values(),
            'visitors' => $visitors->map(fn (SmallGroupVisitor $visitor) => [
                'id' => $visitor->id,
                'visitor_name' => $visitor->visitor_name,
                'guardian_name' => $visitor->guardian_name,
                'contact' => $visitor->contact,
            ])->values(),
            'counts' => [
                'members_present' => $membersPresent,
                'visitors_present' => $visitors->count(),
                'total_attended' => $membersPresent + $visitors->count(),
                'absent_members' => $membersAbsent,
            ],
        ];
    }

    protected function parent(Request $request): ParentGuardian|\Illuminate\Http\JsonResponse
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can use small groups in the app.',
            ], 403);
        }

        return $user;
    }

    protected function churchId(Request $request, ParentGuardian $parent): ?int
    {
        $requested = (int) ($request->get('business_id') ?: 0);

        return $parent->preferredChurchBusinessId($requested ?: null);
    }

    protected function noChurch()
    {
        return response()->json([
            'success' => false,
            'message' => 'Link a church before using small groups.',
        ], 422);
    }
}
