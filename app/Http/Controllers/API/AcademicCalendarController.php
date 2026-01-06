<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    /**
     * List calendar events for the authenticated business.
     */
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = CalendarEvent::query()
            ->with(['creator:id,name,email,branch_id'])
            ->where('business_id', $businessId)
            ->where('status', 'published');

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $query->where('end_date', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->where('start_date', '<=', $endDate);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $month = (int) $request->input('month');
            $year = (int) $request->input('year');
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = (clone $startOfMonth)->endOfMonth();
            $query->whereBetween('start_date', [$startOfMonth, $endOfMonth]);
        }

        if ($request->filled('event_type')) {
            $query->whereIn('event_type', (array) $request->input('event_type'));
        }

        if ($request->filled('priority')) {
            $query->whereIn('priority', (array) $request->input('priority'));
        }

        if ($request->boolean('upcoming_only')) {
            $query->where('end_date', '>=', now());
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Staff members tied to a branch should only see events created by their branch, when applicable.
        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('created_by')
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('creator', function ($creatorQuery) use ($user) {
                        $creatorQuery->where('branch_id', $user->branch_id);
                    });
            });
        }

        $events = $query
            ->orderBy('start_date')
            ->get()
            ->map(fn (CalendarEvent $event) => $this->transformEvent($event));

        return response()->json([
            'success' => true,
            'message' => 'Academic calendar events retrieved successfully.',
            'data' => [
                'events' => $events,
            ],
        ]);
    }

    /**
     * Show a single calendar event.
     */
    public function show(Request $request, $event)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $eventRecord = CalendarEvent::query()
            ->with(['creator:id,name,email,branch_id'])
            ->where('business_id', $businessId)
            ->where('status', 'published')
            ->where(function ($q) use ($event) {
                $q->where('uuid', $event);
                if (is_numeric($event)) {
                    $q->orWhere('id', $event);
                }
            })
            ->first();

        if (!$eventRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found.',
            ], 404);
        }

        if ($user instanceof User && $user->branch_id) {
            $creator = $eventRecord->creator;
            if ($creator && $creator->branch_id && $creator->branch_id !== $user->branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this event.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Calendar event retrieved successfully.',
            'data' => [
                'event' => $this->transformEvent($eventRecord, true),
            ],
        ]);
    }

    /**
     * Transform a CalendarEvent model for API responses.
     */
    protected function transformEvent(CalendarEvent $event, bool $includeMeta = false): array
    {
        $start = $event->start_date instanceof Carbon ? $event->start_date : Carbon::parse($event->start_date);
        $end = $event->end_date instanceof Carbon ? $event->end_date : Carbon::parse($event->end_date);

        $data = [
            'id' => $event->id,
            'uuid' => $event->uuid,
            'title' => $event->title,
            'description' => $event->description,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'start_time' => $event->is_all_day ? null : $start->format('H:i'),
            'end_time' => $event->is_all_day ? null : $end->format('H:i'),
            'is_all_day' => (bool) $event->is_all_day,
            'event_type' => $event->event_type,
            'priority' => $event->priority,
            'color' => $event->color,
            'location' => $event->location,
            'status' => $event->status,
            'day_of_week' => $start->format('l'),
            'start_iso' => $start->toIso8601String(),
            'end_iso' => $end->toIso8601String(),
            'is_today' => $start->isToday(),
            'is_upcoming' => $start->isFuture(),
            'creator' => $event->creator ? [
                'id' => $event->creator->id,
                'name' => $event->creator->name,
                'email' => $event->creator->email,
                'branch_id' => $event->creator->branch_id,
            ] : null,
        ];

        if ($includeMeta) {
            $durationMinutes = $start->diffInMinutes($end);
            $data['duration_minutes'] = $durationMinutes;
            $data['formatted_duration'] = $event->formatted_duration ?? $this->formatDuration($durationMinutes);
        }

        return $data;
    }

    /**
     * Convert duration minutes to a readable label.
     */
    protected function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $remainingMinutes);
        }

        return sprintf('%d minutes', $remainingMinutes);
    }
}

