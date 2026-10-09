<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\SmallGroup;
use App\Models\SmallGroupAttendance;
use App\Models\SmallGroupMeeting;
use App\Models\SmallGroupMember;
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
