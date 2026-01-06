<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicKidsEventsController extends Controller
{
    /**
     * List public kids events with filters
     */
    public function index(Request $request)
    {
        try {
            $query = KidsEvent::query()
                ->with('business:id,name,email,phone,address,website_link,social_media_handles')
                ->where(function ($q) {
                    // Consider published and active-type statuses as visible
                    $q->where('status', 'published')
                      ->orWhere('status', 'ongoing')
                      ->orWhere('status', 'upcoming');
                });

            // Filters
            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }

            if ($featured = $request->query('featured_only')) {
                $query->where('is_featured', filter_var($featured, FILTER_VALIDATE_BOOL));
            }

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $events = $query->orderBy('start_date', 'asc')->get();

            $data = $events->map(function (KidsEvent $event) {
                return $this->transformEvent($event, false);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $data,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicKidsEventsController::index - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching events',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single event
     */
    public function show($id)
    {
        try {
            $event = KidsEvent::with('business:id,name,email,phone,address,website_link,social_media_handles')
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('id', intval($id));
                })->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => $this->transformEvent($event, true),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicKidsEventsController::show - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transformEvent(KidsEvent $event, bool $includeDetails = false): array
    {
        $data = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image_url' => $event->image_url,
            'category' => $event->category,
            'location' => $event->location,
            'price' => $event->price !== null ? (float) $event->price : null,
            'start_date' => $event->start_date?->toISOString(),
            'end_date' => $event->end_date?->toISOString(),
            'status' => $event->status,
            'is_featured' => (bool) $event->is_featured,
            'is_external' => (bool) $event->is_external,
            'business' => $event->business ? [
                'id' => $event->business->id,
                'name' => $event->business->name,
                'email' => $event->business->email,
                'phone' => $event->business->phone,
                'address' => $event->business->address,
                'website_link' => $event->business->website_link,
                'social_media_handles' => $event->business->social_media_handles,
            ] : null,
        ];

        if ($includeDetails) {
            $data['registration_method'] = $event->registration_method; // link | list | in_app
            $data['registration_link'] = $event->registration_link;
            $data['registration_list'] = $event->registration_list;
            $data['organizer'] = [
                'name' => $event->organizer_name,
                'email' => $event->organizer_email,
                'phone' => $event->organizer_phone,
                'address' => $event->organizer_address,
            ];
            $data['social_media_handles'] = $event->social_media_handles;
            $data['contact'] = [
                'email' => $event->contact_email,
                'phone' => $event->contact_phone,
                'info' => $event->contact_info,
            ];
        }

        return $data;
    }
}

