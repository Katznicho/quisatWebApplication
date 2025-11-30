<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicKidsEventsController extends Controller
{
    /**
     * List all kids events (public, no authentication required)
     */
    public function index(Request $request)
    {
        $query = KidsEvent::query()
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('is_featured')
            ->orderBy('start_date');

        // Filter by category
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Filter by status
        if ($status = $request->query('status')) {
            if ($status === 'upcoming') {
                $query->where('start_date', '>', now());
            } elseif ($status === 'ongoing') {
                $query->where('start_date', '<=', now())
                      ->where('end_date', '>=', now());
            } elseif ($status === 'completed') {
                $query->where('end_date', '<', now());
            }
        }

        // Show only featured
        if ($request->boolean('featured_only')) {
            $query->where('is_featured', true);
        }

        // Show only upcoming
        if ($request->boolean('upcoming_only')) {
            $query->where('end_date', '>=', now());
        }

        // Search
        if ($search = $request->query('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('host_organization', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->query('per_page', 20);
        $events = $query->paginate($perPage);

        $transformedEvents = $events->map(function (KidsEvent $event) {
            return $this->transformEvent($event);
        });

        return response()->json([
            'success' => true,
            'message' => 'Kids events retrieved successfully.',
            'data' => [
                'events' => $transformedEvents,
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                ],
                'categories' => KidsEvent::distinct()->pluck('category')->filter()->values(),
            ],
        ]);
    }

    /**
     * Show a single kids event (public)
     */
    public function show($id)
    {
        $event = KidsEvent::where('id', $id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event retrieved successfully.',
            'data' => [
                'event' => $this->transformEvent($event, true),
            ],
        ]);
    }

    /**
     * Transform event for API response
     */
    protected function transformEvent(KidsEvent $event, bool $includeDetails = false): array
    {
        $start = $event->start_date instanceof Carbon ? $event->start_date : Carbon::parse($event->start_date);
        $end = $event->end_date instanceof Carbon ? $event->end_date : Carbon::parse($event->end_date);

        $data = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image_url' => $event->image_url ? (str_starts_with($event->image_url, 'http') ? $event->image_url : asset('storage/' . $event->image_url)) : null,
            'category' => $event->category,
            'host_organization' => $event->host_organization,
            'location' => $event->location,
            'price' => (float) $event->price,
            'formatted_price' => $event->formatted_price,
            'age_groups' => $event->target_age_groups ?: [],
            'duration' => $this->formatDuration($start, $end),
            'schedule' => $this->formatSchedule($start, $end),
            'start_date' => $start->toIso8601String(),
            'end_date' => $end->toIso8601String(),
            'status' => $event->status,
            'is_featured' => (bool) $event->is_featured,
            'spots_available' => $event->spots_available,
            'is_full' => $event->is_full,
            'rating' => $event->rating ? (float) $event->rating : null,
            'total_ratings' => $event->total_ratings ?? 0,
            'requires_parent_permission' => (bool) $event->requires_parent_permission,
        ];

        if ($includeDetails) {
            $data['requirements'] = $event->requirements ?: [];
            $data['contact'] = [
                'info' => $event->contact_info,
                'email' => $event->contact_email,
                'phone' => $event->contact_phone,
            ];
            $data['max_participants'] = $event->max_participants;
            $data['current_participants'] = $event->current_participants;
            $data['business'] = $event->business ? [
                'id' => $event->business->id,
                'name' => $event->business->name,
            ] : null;
        }

        return $data;
    }

    protected function formatDuration(Carbon $start, Carbon $end): string
    {
        $diffMinutes = $start->diffInMinutes($end);
        $hours = intdiv($diffMinutes, 60);
        $minutes = $diffMinutes % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes} minutes";
    }

    protected function formatSchedule(Carbon $start, Carbon $end): string
    {
        $startDay = $start->format('l');
        $startTime = $start->format('g:i A');
        $endTime = $end->format('g:i A');

        if ($start->isSameDay($end)) {
            return "{$startDay} {$startTime} - {$endTime}";
        }

        $endDay = $end->format('l');
        return "{$startDay} {$startTime} - {$endDay} {$endTime}";
    }
}

