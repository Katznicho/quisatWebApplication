<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicAdvertisementsController extends Controller
{
    /**
     * List advertisements (public)
     */
    public function index(Request $request)
    {
        try {
            $query = Advertisement::withTrashed()
                ->with('business:id,name,email,phone,address,website_link,social_media_handles');

            // IMPORTANT: Per requirements, return ALL adverts (even draft/scheduled/paused/expired).
            // We only exclude soft-deleted records.

            // Filters
            if ($mediaType = $request->query('media_type')) {
                $query->where('media_type', $mediaType);
            }
            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }
            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $ads = $query->orderBy('created_at', 'desc')->get();

            $data = $ads->map(function (Advertisement $ad) {
                return $this->transformAdvertisement($ad, false);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'advertisements' => $data,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicAdvertisementsController::index - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching advertisements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single advertisement
     */
    public function show($id)
    {
        try {
            $ad = Advertisement::with('business:id,name,email,phone,address,website_link,social_media_handles')
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('uuid', $id);
                })
                ->first();

            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'advertisement' => $this->transformAdvertisement($ad, true),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicAdvertisementsController::show - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching advertisement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transformAdvertisement(Advertisement $ad, bool $includeDetails = false): array
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
            'id' => $ad->id,
            'uuid' => $ad->uuid,
            'title' => $ad->title,
            'description' => $ad->description,
            'media_type' => $ad->media_type,
            'media_url' => $resolveUrl($ad->media_path),
            'start_date' => $ad->start_date?->toISOString(),
            'end_date' => $ad->end_date?->toISOString(),
            'status' => $ad->status,
            'category' => $ad->category,
            'business' => $ad->business ? [
                'id' => $ad->business->id,
                'name' => $ad->business->name,
                'email' => $ad->business->email,
                'phone' => $ad->business->phone,
                'address' => $ad->business->address,
                'website_link' => $ad->business->website_link,
                'social_media_handles' => $ad->business->social_media_handles,
            ] : null,
        ];

        if ($includeDetails) {
            $data['budget'] = $ad->budget ? (float) $ad->budget : null;
            $data['target_audience'] = $ad->target_audience;
            $data['is_active'] = $ad->isActive();
        }

        return $data;
    }
}

