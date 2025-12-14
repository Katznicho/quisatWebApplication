<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimetableController extends Controller
{
    /**
     * Get timetable entries for the authenticated user/business.
     */
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = Timetable::query()
            ->with([
                'subject:id,name,code',
                'classRoom:id,name,code',
                'teacher:id,name,email',
            ])
            ->where('business_id', $businessId)
            ->where('status', 'active');

        // Filter by day of week if provided
        if ($dayOfWeek = $request->query('day_of_week')) {
            $query->where('day_of_week', strtolower($dayOfWeek));
        }

        // Filter by class if provided
        if ($classRoomId = $request->query('class_room_id')) {
            $query->where('class_room_id', $classRoomId);
        }

        // Filter by teacher if provided
        if ($teacherId = $request->query('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }

        // If user is a staff member, optionally filter by their classes
        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        $timetables = $query
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(function (Timetable $timetable) {
                return $this->transformTimetable($timetable);
            });

        // Group by day of week
        $grouped = $timetables->groupBy('day_of_week')->map(function ($items) {
            return $items->values();
        });

        return response()->json([
            'success' => true,
            'message' => 'Timetable retrieved successfully.',
            'data' => [
                'timetables' => $timetables,
                'grouped_by_day' => $grouped,
            ],
        ]);
    }

    /**
     * Get today's timetable.
     */
    public function today(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $today = Carbon::now();
        $dayOfWeek = strtolower($today->format('l')); // monday, tuesday, etc.

        $query = Timetable::query()
            ->with([
                'subject:id,name,code',
                'classRoom:id,name,code',
                'teacher:id,name,email',
            ])
            ->where('business_id', $businessId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active');

        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        $timetables = $query
            ->orderBy('start_time')
            ->get()
            ->map(function (Timetable $timetable) {
                return $this->transformTimetable($timetable);
            });

        return response()->json([
            'success' => true,
            'message' => 'Today\'s timetable retrieved successfully.',
            'data' => [
                'timetables' => $timetables,
                'day' => $dayOfWeek,
            ],
        ]);
    }

    /**
     * Transform timetable for API response.
     */
    protected function transformTimetable(Timetable $timetable): array
    {
        return [
            'id' => $timetable->id,
            'uuid' => $timetable->uuid ?? null,
            'day_of_week' => $timetable->day_of_week,
            'start_time' => optional($timetable->start_time)->format('H:i'),
            'end_time' => optional($timetable->end_time)->format('H:i'),
            'room_number' => $timetable->room_number,
            'notes' => $timetable->notes,
            'subject' => $timetable->subject ? [
                'id' => $timetable->subject->id,
                'name' => $timetable->subject->name,
                'code' => $timetable->subject->code,
            ] : null,
            'class_room' => $timetable->classRoom ? [
                'id' => $timetable->classRoom->id,
                'name' => $timetable->classRoom->name,
                'code' => $timetable->classRoom->code,
            ] : null,
            'teacher' => $timetable->teacher ? [
                'id' => $timetable->teacher->id,
                'name' => $timetable->teacher->name,
                'email' => $timetable->teacher->email,
            ] : null,
        ];
    }
}
