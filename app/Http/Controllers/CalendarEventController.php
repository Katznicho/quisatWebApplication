<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\EventNotification;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = CalendarEvent::where('business_id', Auth::user()->business_id)
            ->with(['creator', 'notifications'])
            ->orderBy('start_date', 'asc')
            ->get();

        return view('calendar-events.index', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classrooms = ClassRoom::where('business_id', Auth::user()->business_id)->get();
        $students = Student::where('business_id', Auth::user()->business_id)->get();
        $teachers = User::where('business_id', Auth::user()->business_id)
            ->whereHas('role', function($query) {
                $query->where('name', 'Staff');
            })->get();

        return view('calendar-events.create', compact('classrooms', 'students', 'teachers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'event_type' => 'required|in:meeting,exam,holiday,workshop,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'is_all_day' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly,yearly',
            'recurrence_days' => 'nullable|array',
            'recurrence_end_date' => 'nullable|required_if:is_recurring,1|date|after:start_date',
        ]);

        $event = CalendarEvent::create([
            'business_id' => Auth::user()->business_id,
            'created_by' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'color' => $request->color ?? '#3B82F6',
            'event_type' => $request->event_type,
            'priority' => $request->priority,
            'is_all_day' => $request->boolean('is_all_day'),
            'is_recurring' => $request->boolean('is_recurring'),
            'recurrence_pattern' => $request->recurrence_pattern,
            'recurrence_days' => $request->recurrence_days,
            'recurrence_end_date' => $request->recurrence_end_date,
            'status' => 'active',
        ]);

        // Create notifications if specified
        if ($request->has('notifications')) {
            $this->createNotifications($event, $request->notifications);
        }

        return redirect()->route('calendar-events.index')
            ->with('success', 'Event created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(CalendarEvent $calendarEvent)
    {
        $calendarEvent->load(['creator', 'notifications', 'business']);
        
        return view('calendar-events.show', compact('calendarEvent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CalendarEvent $calendarEvent)
    {
        $classrooms = ClassRoom::where('business_id', Auth::user()->business_id)->get();
        $students = Student::where('business_id', Auth::user()->business_id)->get();
        $teachers = User::where('business_id', Auth::user()->business_id)
            ->whereHas('role', function($query) {
                $query->where('name', 'Staff');
            })->get();

        return view('calendar-events.edit', compact('calendarEvent', 'classrooms', 'students', 'teachers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'event_type' => 'required|in:meeting,exam,holiday,workshop,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'is_all_day' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly,yearly',
            'recurrence_days' => 'nullable|array',
            'recurrence_end_date' => 'nullable|required_if:is_recurring,1|date|after:start_date',
        ]);

        $calendarEvent->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'color' => $request->color ?? '#3B82F6',
            'event_type' => $request->event_type,
            'priority' => $request->priority,
            'is_all_day' => $request->boolean('is_all_day'),
            'is_recurring' => $request->boolean('is_recurring'),
            'recurrence_pattern' => $request->recurrence_pattern,
            'recurrence_days' => $request->recurrence_days,
            'recurrence_end_date' => $request->recurrence_end_date,
        ]);

        return redirect()->route('calendar-events.index')
            ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CalendarEvent $calendarEvent)
    {
        $calendarEvent->delete();
        
        return redirect()->route('calendar-events.index')
            ->with('success', 'Event deleted successfully!');
    }

    /**
     * Display calendar view
     */
    public function calendar()
    {
        $events = CalendarEvent::where('business_id', Auth::user()->business_id)
            ->with(['creator'])
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_date,
                    'end' => $event->end_date,
                    'color' => $event->color,
                    'allDay' => $event->is_all_day,
                    'url' => route('calendar-events.show', $event->id),
                ];
            });

        return view('calendar-events.calendar', compact('events'));
    }

    /**
     * Create notifications for an event
     */
    private function createNotifications($event, $notifications)
    {
        foreach ($notifications as $notification) {
            EventNotification::create([
                'calendar_event_id' => $event->id,
                'business_id' => Auth::user()->business_id,
                'title' => $notification['title'],
                'message' => $notification['message'],
                'notification_type' => $notification['type'] ?? 'email',
                'target_type' => $notification['target_type'] ?? 'all',
                'target_ids' => $notification['target_ids'] ?? [],
                'target_filters' => $notification['target_filters'] ?? [],
                'scheduled_at' => $notification['scheduled_at'] ?? now(),
                'reminder_minutes' => $notification['reminder_minutes'] ?? 0,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Get events for API (JSON)
     */
    public function apiEvents()
    {
        $events = CalendarEvent::where('business_id', Auth::user()->business_id)
            ->with(['creator'])
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_date,
                    'end' => $event->end_date,
                    'color' => $event->color,
                    'allDay' => $event->is_all_day,
                    'event_type' => $event->event_type,
                    'priority' => $event->priority,
                    'location' => $event->location,
                    'description' => $event->description,
                ];
            });

        return response()->json($events);
    }

    /**
     * Get upcoming events
     */
    public function upcoming()
    {
        $events = CalendarEvent::where('business_id', Auth::user()->business_id)
            ->where('start_date', '>=', now())
            ->with(['creator'])
            ->orderBy('start_date', 'asc')
            ->limit(10)
            ->get();

        return view('calendar-events.upcoming', compact('events'));
    }
}
