<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsFunVenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicKidsFunVenuesController extends Controller
{
    /**
     * List all kids fun venues (public, only published)
     */
    public function index(Request $request)
    {
        try {
            // Query for published venues only
            $query = KidsFunVenue::query()
                ->where('status', 'published')
                ->with('business:id,name,email,phone,address,website_link,social_media_handles')
                ->orderBy('created_at', 'desc');

            // Search
            if ($search = $request->query('search')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }

            // Get all venues (no pagination for now, can add if needed)
            $venues = $query->get();

            $transformedVenues = $venues->map(function (KidsFunVenue $venue) {
                return $this->transformVenue($venue, false);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'venues' => $transformedVenues,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('PublicKidsFunVenuesController::index - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching venues',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific venue
     */
    public function show($uuid)
    {
        try {
            $venue = KidsFunVenue::where('uuid', $uuid)
                ->where('status', 'published')
                ->with('business:id,name,email,phone,address,shop_number,website_link,social_media_handles')
                ->first();

            if (!$venue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venue not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'venue' => $this->transformVenue($venue, true),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('PublicKidsFunVenuesController::show - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching venue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform venue for API response
     */
    private function transformVenue(KidsFunVenue $venue, bool $includeDetails = false)
    {
        $data = [
            'id' => $venue->id,
            'uuid' => $venue->uuid,
            'name' => $venue->name,
            'description' => $venue->description,
            'location' => $venue->location,
            'open_time' => $venue->open_time,
            'close_time' => $venue->close_time,
            'main_image_url' => $venue->main_image_path ? Storage::url($venue->main_image_path) : null,
            'status' => $venue->status,
            'created_at' => $venue->created_at?->toISOString(),
            'updated_at' => $venue->updated_at?->toISOString(),
            'business' => $venue->business ? [
                'id' => $venue->business->id,
                'name' => $venue->business->name,
                'email' => $venue->business->email,
                'phone' => $venue->business->phone,
                'address' => $venue->business->address,
                'shop_number' => $venue->business->shop_number,
                'website_link' => $venue->business->website_link,
                'social_media_handles' => $venue->business->social_media_handles,
            ] : null,
        ];

        if ($includeDetails) {
            $data['activities'] = $venue->activities;
            $data['prices'] = $venue->prices;
            $data['social_media_handles'] = $venue->social_media_handles;
            $data['website_link'] = $venue->website_link;
            $data['booking_link'] = $venue->booking_link;
            $data['gallery_image_urls'] = collect($venue->gallery_image_paths ?? [])->map(fn ($path) => Storage::url($path))->toArray();
        }

        return $data;
    }
}
