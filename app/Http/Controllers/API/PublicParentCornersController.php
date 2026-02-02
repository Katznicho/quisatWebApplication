<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentCorner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicParentCornersController extends Controller
{
    /**
     * List public parent corner events with filters
     */
    public function index(Request $request)
    {
        try {
            $query = ParentCorner::query()
                ->with('business:id,name,email,phone,address,website_link,social_media_handles');

            // Filters
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

            // Upcoming-only filter (used by app)
            if (filter_var($request->query('upcoming_only'), FILTER_VALIDATE_BOOL)) {
                $query->where('start_date', '>=', now());
            }

            $events = $query->orderBy('start_date', 'asc')->get();

            $data = $events->map(function (ParentCorner $event) {
                return $this->transformEvent($event, false);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $data,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicParentCornersController::index - ' . $e->getMessage());
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
            $event = ParentCorner::with('business:id,name,email,phone,address,website_link,social_media_handles')
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('id', intval($id));
                })
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $transformedEvent = $this->transformEvent($event, true);
            
            // Log the full response to see what's being returned
            Log::info('PublicParentCornersController::show - Full Response', [
                'event_id' => $id,
                'event_title' => $event->title,
                'organizer' => $transformedEvent['organizer'] ?? null,
                'contact' => $transformedEvent['contact'] ?? null,
                'organizer_name' => $event->organizer_name,
                'organizer_email' => $event->organizer_email,
                'organizer_phone' => $event->organizer_phone,
                'organizer_address' => $event->organizer_address,
                'contact_email' => $event->contact_email,
                'contact_phone' => $event->contact_phone,
                'contact_info' => $event->contact_info,
                'full_response' => $transformedEvent,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'event' => $transformedEvent,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicParentCornersController::show - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transformEvent(ParentCorner $event, bool $includeDetails = false): array
    {
        $resolveUrl = function (?string $pathOrUrl): ?string {
            if (!$pathOrUrl) {
                return null;
            }
            if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
                return $pathOrUrl;
            }
            return Storage::url($pathOrUrl);
        };

        $data = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image_url' => $resolveUrl($event->image_url),
            'category' => $event->category,
            'location' => $event->location,
            'price' => $event->price !== null ? (float) $event->price : null,
            'formatted_price' => $event->formatted_price ?? ($event->price > 0 ? ('UGX ' . number_format((float) $event->price, 0)) : 'Free'),
            'start_date' => $event->start_date?->toISOString(),
            'end_date' => $event->end_date?->toISOString(),
            'status' => $event->status,
            'is_featured' => (bool) $event->is_featured,
            'spots_available' => $event->spots_available ?? 999,
            'is_full' => (bool) ($event->is_full ?? false),
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
            $data['registration_method'] = $event->registration_method;
            $data['registration_link'] = $event->registration_link;
            $data['registration_list'] = $event->registration_list;
            $data['organizer'] = [
                'name' => $event->organizer_name,
                'email' => $event->organizer_email,
                'phone' => $event->organizer_phone,
                'address' => $event->organizer_address,
            ];
            $data['social_media_handles'] = $event->social_media_handles;
            // Use business email and phone as contact details
            $data['contact'] = [
                'email' => $event->business->email ?? null,
                'phone' => $event->business->phone ?? null,
                'info' => $event->contact_info,
            ];
        }

        return $data;
    }
}
