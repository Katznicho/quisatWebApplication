<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicKidsEventsController extends Controller
{
    /**
     * List all kids events (public, no authentication required)
     */
    public function index(Request $request)
    {
        try {
            Log::info('PublicKidsEventsController::index - Starting request');
            
            $query = KidsEvent::query()
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
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

            Log::info('PublicKidsEventsController::index - Executing query');
            // Get all events (no pagination)
            $events = $query->get();
            Log::info('PublicKidsEventsController::index - Found ' . $events->count() . ' events');

            Log::info('PublicKidsEventsController::index - Transforming events');
            $transformedEvents = $events->map(function (KidsEvent $event) {
                try {
                    return $this->transformEvent($event);
                } catch (\Exception $e) {
                    Log::error('PublicKidsEventsController::index - Error transforming event ' . $event->id . ': ' . $e->getMessage());
                    // Return minimal data instead of failing completely
                    return [
                        'id' => $event->id,
                        'title' => $event->title ?? 'Error loading event',
                        'description' => 'Error loading details',
                        'error' => true,
                    ];
                }
            })->filter(); // Remove any null entries

            Log::info('PublicKidsEventsController::index - Getting categories');
            $categories = KidsEvent::distinct()->pluck('category')->filter()->values();

            Log::info('PublicKidsEventsController::index - Returning response');
            return response()->json([
                'success' => true,
                'message' => 'Kids events retrieved successfully.',
                'data' => [
                    'events' => $transformedEvents,
                    'total' => $transformedEvents->count(),
                    'categories' => $categories,
                ],
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicKidsEventsController::index - Error: ' . $errorMessage, [
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving kids events.',
                'error' => [
                    'message' => (string) $errorMessage,
                    'file' => (string) $errorFile,
                    'line' => (int) $errorLine,
                    'type' => get_class($e),
                ],
            ], 500);
        }
    }

    /**
     * Show a single kids event (public)
     */
    public function show($id)
    {
        try {
            Log::info('PublicKidsEventsController::show - Requesting event ID: ' . $id);
            
            $event = KidsEvent::where('id', $id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if (!$event) {
                Log::warning('PublicKidsEventsController::show - Event not found: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.',
                ], 404);
            }

            Log::info('PublicKidsEventsController::show - Transforming event');
            $transformedEvent = $this->transformEvent($event, true);

            return response()->json([
                'success' => true,
                'message' => 'Event retrieved successfully.',
                'data' => [
                    'event' => $transformedEvent,
                ],
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicKidsEventsController::show - Error: ' . $errorMessage, [
                'id' => $id,
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the event.',
                'error' => [
                    'message' => (string) $errorMessage,
                    'file' => (string) $errorFile,
                    'line' => (int) $errorLine,
                    'type' => get_class($e),
                ],
            ], 500);
        }
    }

    /**
     * Transform event for API response
     */
    protected function transformEvent(KidsEvent $event, bool $includeDetails = false): array
    {
        try {
            $start = null;
            $end = null;
            
            if ($event->start_date) {
                $start = $event->start_date instanceof Carbon ? $event->start_date : Carbon::parse($event->start_date);
            }
            
            if ($event->end_date) {
                $end = $event->end_date instanceof Carbon ? $event->end_date : Carbon::parse($event->end_date);
            }

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
            'duration' => $start && $end ? $this->formatDuration($start, $end) : 'N/A',
            'schedule' => $start && $end ? $this->formatSchedule($start, $end) : 'N/A',
            'start_date' => $start ? $start->toIso8601String() : null,
            'end_date' => $end ? $end->toIso8601String() : null,
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
        } catch (\Exception $e) {
            Log::error('PublicKidsEventsController::transformEvent - Error: ' . $e->getMessage(), [
                'event_id' => $event->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    protected function formatDuration(?Carbon $start, ?Carbon $end): string
    {
        if (!$start || !$end) {
            return 'N/A';
        }
        
        $diffMinutes = $start->diffInMinutes($end);
        $hours = intdiv($diffMinutes, 60);
        $minutes = $diffMinutes % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes} minutes";
    }

    protected function formatSchedule(?Carbon $start, ?Carbon $end): string
    {
        if (!$start || !$end) {
            return 'N/A';
        }
        
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

