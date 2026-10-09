<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\StaffAttendanceService;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    public function __construct(
        protected StaffAttendanceService $attendance
    ) {}

    public function today(Request $request)
    {
        $actor = $this->staffUser($request);
        if (! $actor) {
            return $this->staffOnly();
        }

        $subject = $this->subjectUser($request, $actor);
        if ($subject instanceof \Illuminate\Http\JsonResponse) {
            return $subject;
        }

        $today = $this->attendance->todayPayload($subject);

        return response()->json([
            'success' => true,
            'message' => 'Staff attendance loaded.',
            'data' => array_merge($today, [
                'staff' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ],
                'organisation' => $subject->business?->name,
                'branch' => $subject->branch?->name,
                'can_record_for_others' => $actor->canRecordStaffAttendanceForOthers(),
                'is_self' => (int) $actor->id === (int) $subject->id,
            ]),
        ]);
    }

    public function history(Request $request)
    {
        $actor = $this->staffUser($request);
        if (! $actor) {
            return $this->staffOnly();
        }

        $subject = $this->subjectUser($request, $actor);
        if ($subject instanceof \Illuminate\Http\JsonResponse) {
            return $subject;
        }

        $records = StaffAttendance::query()
            ->with(['business', 'branch', 'user', 'recordedBy', 'checkedOutBy'])
            ->where('user_id', $subject->id)
            ->where('business_id', $subject->business_id)
            ->orderByDesc('checked_in_at')
            ->limit(60)
            ->get()
            ->map(fn (StaffAttendance $record) => $this->attendance->transform($record));

        return response()->json([
            'success' => true,
            'message' => 'Attendance history loaded.',
            'data' => [
                'history' => $records,
            ],
        ]);
    }

    public function colleagues(Request $request)
    {
        $actor = $this->staffUser($request);
        if (! $actor) {
            return $this->staffOnly();
        }

        if (! $actor->canRecordStaffAttendanceForOthers()) {
            return response()->json([
                'success' => true,
                'data' => ['staff' => [[
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'branch' => $actor->branch?->name,
                ]]],
            ]);
        }

        $query = User::query()
            ->with('branch:id,name')
            ->where('business_id', $actor->business_id)
            ->orderBy('name');

        if (! $actor->isAdmin() && ! $actor->isBusinessAdmin() && $actor->branch_id) {
            $query->where('branch_id', $actor->branch_id);
        }

        $staff = $query->get(['id', 'name', 'branch_id'])->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'branch' => $user->branch?->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['staff' => $staff],
        ]);
    }

    public function checkIn(Request $request)
    {
        return $this->record($request, 'checkIn');
    }

    public function checkOut(Request $request)
    {
        return $this->record($request, 'checkOut');
    }

    protected function record(Request $request, string $method)
    {
        $actor = $this->staffUser($request);
        if (! $actor) {
            return $this->staffOnly();
        }

        $subject = $this->subjectUser($request, $actor);
        if ($subject instanceof \Illuminate\Http\JsonResponse) {
            return $subject;
        }

        $result = $this->attendance->{$method}($actor, $subject);
        $record = $result['record'];

        return response()->json([
            'success' => $result['saved'],
            'message' => $result['message'],
            'data' => $record ? [
                'attendance' => $this->attendance->transform($record),
                'today' => $this->attendance->todayPayload($subject),
            ] : null,
        ], $result['saved'] ? 200 : $result['status']);
    }

    protected function subjectUser(Request $request, User $actor): User|\Illuminate\Http\JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', $actor->id));
        if ($userId === (int) $actor->id) {
            $actor->loadMissing(['business', 'branch']);

            return $actor;
        }

        $subject = User::query()->with(['business', 'branch'])->find($userId);
        if (! $subject) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
            ], 404);
        }

        $denied = $this->attendance->assertCanRecord($actor, $subject);
        if ($denied) {
            return response()->json([
                'success' => false,
                'message' => $denied,
            ], 403);
        }

        return $subject;
    }

    protected function staffUser(Request $request): ?User
    {
        $user = $request->get('authenticated_user');

        return $user instanceof User ? $user : null;
    }

    protected function staffOnly()
    {
        return response()->json([
            'success' => false,
            'message' => 'Staff attendance is only available to staff.',
        ], 403);
    }
}
