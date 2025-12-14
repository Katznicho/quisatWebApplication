<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BroadcastAnnouncement;
use App\Models\ClassAssignment;
use App\Models\CalendarEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Only staff users can access this resource.',
            ], 403);
        }

        $timezone = config('app.timezone', 'Africa/Nairobi');
        $today = Carbon::now($timezone);
        $dayOfWeek = (int) $today->dayOfWeekIso; // 1 (Mon) - 7 (Sun)

        $assignmentsDue = ClassAssignment::where('business_id', $business->id)
            ->where('status', 'published')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->count();

        // Count announcements - include both 'published' and 'sent' status
        $announcementsNew = BroadcastAnnouncement::where('business_id', $business->id)
            ->whereIn('status', ['published', 'sent'])
            ->where(function ($query) use ($today) {
                $query->whereNull('sent_at')
                    ->orWhere('sent_at', '>=', $today->copy()->subDays(7))
                    ->orWhere('created_at', '>=', $today->copy()->subDays(7));
            })
            ->count();

        $studentsTotal = Student::where('business_id', $business->id)->count();
        $parentsTotal = ParentGuardian::where('business_id', $business->id)->count();

        // Count unread messages for the user
        $unreadMessagesCount = 0;
        $userConversations = Conversation::where('business_id', $business->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($userConversations as $conversation) {
            $unreadCount = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
            $unreadMessagesCount += $unreadCount;
        }

        $schedule = Timetable::query()
            ->with(['subject:id,name', 'classRoom:id,name,code', 'teacher:id,name'])
            ->where('business_id', $business->id)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->limit(10)
            ->get()
            ->map(function (Timetable $entry) use ($today) {
                return [
                    'id' => $entry->id,
                    'subject' => $entry->subject?->name ?? 'Unknown Subject',
                    'class' => $entry->classRoom?->name ?? 'N/A',
                    'room' => $entry->room_number,
                    'start_time' => optional($entry->start_time)->format('H:i'),
                    'end_time' => optional($entry->end_time)->format('H:i'),
                    'teacher' => $entry->teacher?->name,
                ];
            })
            ->values();

        $upcomingEvents = CalendarEvent::query()
            ->where('business_id', $business->id)
            ->where('status', 'published')
            ->where('start_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(3)
            ->get()
            ->map(function (CalendarEvent $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => optional($event->start_date)->toIso8601String(),
                    'end_date' => optional($event->end_date)->toIso8601String(),
                    'location' => $event->location,
                    'event_type' => $event->event_type,
                    'priority' => $event->priority,
                ];
            })
            ->values();

        $recentAnnouncements = BroadcastAnnouncement::query()
            ->where('business_id', $business->id)
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit(3)
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

        $recentAssignments = ClassAssignment::query()
            ->with(['classRoom:id,name,code', 'subject:id,name'])
            ->where('business_id', $business->id)
            ->orderByDesc('assigned_date')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function (ClassAssignment $assignment) {
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'assignment_type' => $assignment->assignment_type,
                    'subject' => $assignment->subject?->name,
                    'class_room' => $assignment->classRoom?->name,
                    'due_date' => optional($assignment->due_date)->toIso8601String(),
                    'status' => $assignment->status,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data loaded successfully.',
            'data' => [
                'quick_stats' => [
                    'assignments_due' => $assignmentsDue,
                    'announcements_new' => $announcementsNew,
                    'students_total' => $studentsTotal,
                    'parents_total' => $parentsTotal,
                    'unread_messages' => $unreadMessagesCount,
                ],
                'today_schedule' => $schedule,
                'upcoming_events' => $upcomingEvents,
                'recent_announcements' => $recentAnnouncements,
                'recent_assignments' => $recentAssignments,
            ],
        ]);
    }
}
