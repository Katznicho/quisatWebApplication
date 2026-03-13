<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportChild;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportChildController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');

        $query = SupportChild::query()
            ->with('images')
            ->where('business_id', $businessId)
            ->where(function (Builder $q) {
                $q->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('is_featured', 'desc')
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('child_name', 'like', "%{$search}%")
                    ->orWhere('organisation_name', 'like', "%{$search}%")
                    ->orWhere('story', 'like', "%{$search}%");
            });
        }

        $children = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Support children loaded successfully.',
            'data' => [
                'children' => $children->map(fn (SupportChild $c) => $this->transformChild($c, false)),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $businessId = $request->get('business_id');

        $child = SupportChild::with('images')
            ->where('business_id', $businessId)
            ->where(function (Builder $q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (! $child) {
            return response()->json([
                'success' => false,
                'message' => 'Support child record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Support child loaded successfully.',
            'data' => [
                'child' => $this->transformChild($child, true),
            ],
        ]);
    }

    protected function transformChild(SupportChild $child, bool $includeDetails = false): array
    {
        $resolveUrl = function (?string $pathOrUrl): ?string {
            if (! $pathOrUrl) {
                return null;
            }
            if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
                return $pathOrUrl;
            }
            return Storage::url($pathOrUrl);
        };

        $images = $child->images
            ? $child->images
                ->sortBy(fn ($img) => [$img->is_primary ? 0 : 1, $img->sort_order, $img->id])
                ->values()
                ->map(function ($img) use ($resolveUrl) {
                    return [
                        'id' => $img->id,
                        'url' => $resolveUrl($img->image_url),
                        'is_primary' => (bool) $img->is_primary,
                        'sort_order' => (int) $img->sort_order,
                    ];
                })
                ->toArray()
            : [];

        $mainImage = count($images) > 0 ? $images[0]['url'] ?? null : null;

        $data = [
            'id' => $child->id,
            'uuid' => $child->uuid,
            'child_name' => $child->child_name,
            'age' => $child->age,
            'monthly_fee' => $child->monthly_fee !== null ? (float) $child->monthly_fee : null,
            'currency' => $child->currency,
            'story' => $child->story,
            'organisation_name' => $child->organisation_name,
            'organisation_email' => $child->organisation_email,
            'organisation_phone' => $child->organisation_phone,
            'organisation_website' => $child->organisation_website,
            'status' => $child->status,
            'is_featured' => (bool) $child->is_featured,
            'image_url' => $mainImage,
            'images' => $images,
        ];

        if ($includeDetails) {
            // Currently same as base; reserved for future extra meta.
        }

        return $data;
    }
}

