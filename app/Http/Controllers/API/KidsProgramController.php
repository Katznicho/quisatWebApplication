<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KidsProgramController extends Controller
{
    /**
     * List kids programs/events for the authenticated business.
     */
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');

        $query = KidsEvent::query()
            ->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)
                    ->orWhereNull('business_id'); // allow global programs
            })
            ->orderByDesc('is_featured')
            ->orderBy('start_date');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($request->boolean('featured_only')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('upcoming_only', true)) {
            $query->where('end_date', '>=', now());
        }

        if ($search = $request->query('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('host_organization', 'like', "%{$search}%");
            });
        }

        $programs = $query->limit(100)->get()->map(function (KidsEvent $event) {
            return $this->transformProgram($event);
        });

        return response()->json([
            'success' => true,
            'message' => 'Kids programs retrieved successfully.',
            'data' => [
                'programs' => $programs,
                'categories' => $programs->pluck('category')->unique()->values(),
            ],
        ]);
    }

    /**
     * Show a single kids program.
     */
    public function show(Request $request, $program)
    {
        $businessId = $request->get('business_id');

        $event = KidsEvent::query()
            ->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)
                    ->orWhereNull('business_id');
            })
            ->where(function ($q) use ($program) {
                $q->where('id', $program);
                if (is_numeric($program)) {
                    $q->orWhere('id', $program);
                }
            })
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Program retrieved successfully.',
            'data' => [
                'program' => $this->transformProgram($event, true),
            ],
        ]);
    }

    /**
     * Transform program for API response.
     */
    protected function transformProgram(KidsEvent $event, bool $includeDetails = false): array
    {
        $start = $event->start_date instanceof Carbon ? $event->start_date : Carbon::parse($event->start_date);
        $end = $event->end_date instanceof Carbon ? $event->end_date : Carbon::parse($event->end_date);

        $data = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image_url' => $event->image_url,
            'category' => $event->category,
            'host_organization' => $event->host_organization,
            'location' => $event->location,
            'price' => $event->price,
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
            'rating' => $event->rating,
            'total_ratings' => $event->total_ratings,
            'requires_parent_permission' => (bool) $event->requires_parent_permission,
        ];

        if ($includeDetails) {
            $data['requirements'] = $event->requirements ?: [];
            $data['contact'] = [
                'info' => $event->contact_info,
                'email' => $event->contact_email,
                'phone' => $event->contact_phone,
            ];
            $data['business_id'] = $event->business_id;
            $data['created_by'] = $event->created_by;
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
            return "{$startDay}s {$startTime} - {$endTime}";
        }

        $endDay = $end->format('l');

        return "{$startDay} {$startTime} - {$endDay} {$endTime}";
    }
}

