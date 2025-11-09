<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BroadcastAnnouncement;
use App\Models\CalendarEvent;
use App\Models\ClassAssignment;
use App\Models\ParentGuardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ParentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (!$user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents/guardians can access this resource.',
            ], 403);
        }

        $timezone = config('app.timezone', 'Africa/Nairobi');
        $today = Carbon::now($timezone);
        $children = $user->students()->with(['classRoom:id,name,code'])->get();
        $classRoomIds = $children->pluck('class_room_id')->filter()->unique()->values();

        $announcements = BroadcastAnnouncement::query()
            ->where('business_id', $business->id)
            ->where('status', 'sent')
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (BroadcastAnnouncement $announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'type' => $announcement->type,
                    'sent_at' => optional($announcement->sent_at)->toIso8601String(),
                ];
            })
            ->values();

        $events = CalendarEvent::query()
            ->where('business_id', $business->id)
            ->where('status', 'published')
            ->where('start_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(4)
            ->get()
            ->map(function (CalendarEvent $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start_date' => optional($event->start_date)->toIso8601String(),
                    'end_date' => optional($event->end_date)->toIso8601String(),
                    'location' => $event->location,
                    'event_type' => $event->event_type,
                ];
            })
            ->values();

        $assignmentsQuery = ClassAssignment::query()
            ->with(['classRoom:id,name,code', 'subject:id,name'])
            ->where('business_id', $business->id)
            ->where('status', 'published')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->orderBy('due_date');

        if ($classRoomIds->isNotEmpty()) {
            $assignmentsQuery->whereIn('class_room_id', $classRoomIds);
        }

        $assignments = $assignmentsQuery
            ->limit(6)
            ->get()
            ->map(function (ClassAssignment $assignment) {
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'due_date' => optional($assignment->due_date)->toIso8601String(),
                    'class_room' => $assignment->classRoom?->name,
                    'subject' => $assignment->subject?->name,
                    'assignment_type' => $assignment->assignment_type,
                ];
            })
            ->values();

        $childrenData = $children->map(function (Student $student) {
            return [
                'id' => $student->id,
                'uuid' => $student->uuid,
                'full_name' => $student->full_name,
                'class' => $student->classRoom?->name,
                'class_room_id' => $student->class_room_id,
                'student_id' => $student->student_id,
                'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($student->full_name) . "&background=4A90E2&color=ffffff",
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Parent dashboard data loaded successfully.',
            'data' => [
                'children' => $childrenData,
                'announcements' => $announcements,
                'upcoming_events' => $events,
                'upcoming_assignments' => $assignments,
            ],
        ]);
    }
}
