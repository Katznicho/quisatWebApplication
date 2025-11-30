<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class PublicAdvertisementsController extends Controller
{
    /**
     * List all active advertisements (public, no authentication required)
     */
    public function index(Request $request)
    {
        $query = Advertisement::query()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('created_at');

        // Filter by category
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Filter by media type
        if ($mediaType = $request->query('media_type')) {
            $query->where('media_type', $mediaType);
        }

        // Search
        if ($search = $request->query('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Get all advertisements (no pagination)
        $advertisements = $query->get();

        $transformedAds = $advertisements->map(function (Advertisement $ad) {
            return $this->transformAdvertisement($ad);
        });

        return response()->json([
            'success' => true,
            'message' => 'Advertisements retrieved successfully.',
            'data' => [
                'advertisements' => $transformedAds,
                'total' => $transformedAds->count(),
                'categories' => Advertisement::where('status', 'active')
                    ->distinct()
                    ->pluck('category')
                    ->filter()
                    ->values(),
            ],
        ]);
    }

    /**
     * Show a single advertisement (public)
     */
    public function show($id)
    {
        $advertisement = Advertisement::where('id', $id)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$advertisement) {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement not found or not active.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advertisement retrieved successfully.',
            'data' => [
                'advertisement' => $this->transformAdvertisement($advertisement, true),
            ],
        ]);
    }

    /**
     * Transform advertisement for API response
     */
    protected function transformAdvertisement(Advertisement $ad, bool $includeDetails = false): array
    {
        $data = [
            'id' => $ad->id,
            'uuid' => $ad->uuid,
            'title' => $ad->title,
            'description' => $ad->description,
            'media_type' => $ad->media_type,
            'media_url' => $ad->media_path ? (str_starts_with($ad->media_path, 'http') ? $ad->media_path : asset('storage/' . $ad->media_path)) : null,
            'category' => $ad->category,
            'start_date' => $ad->start_date->toIso8601String(),
            'end_date' => $ad->end_date->toIso8601String(),
            'is_recurring' => (bool) $ad->is_recurring,
            'recurrence_pattern' => $ad->recurrence_pattern,
        ];

        if ($includeDetails) {
            $data['target_audience'] = $ad->target_audience ?: [];
            $data['budget'] = $ad->budget ? (float) $ad->budget : null;
            $data['business'] = $ad->business ? [
                'id' => $ad->business->id,
                'name' => $ad->business->name,
                'logo' => $ad->business->logo ? asset('storage/' . $ad->business->logo) : null,
            ] : null;
        }

        return $data;
    }
}

