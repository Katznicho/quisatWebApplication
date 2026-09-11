<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MemoryWallItem;
use App\Models\User;
use Illuminate\Http\Request;

class MemoryWallController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');

        $items = MemoryWallItem::query()
            ->where('business_id', $businessId)
            ->current()
            ->latest()
            ->get()
            ->map(fn (MemoryWallItem $item) => $this->transform($item));

        $verse = $items->firstWhere('type', 'memory_verse');
        $focus = $items->firstWhere('type', 'prayer_focus');

        return response()->json([
            'success' => true,
            'data' => [
                'memory_verse' => $verse,
                'prayer_focus' => $focus,
                'items' => $items->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Only staff can update the memory wall.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:memory_verse,prayer_focus',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:2000',
            'scripture_ref' => 'nullable|string|max:120',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
        ]);

        $item = MemoryWallItem::create([
            ...$validated,
            'business_id' => $request->get('business_id'),
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['item' => $this->transform($item)],
        ], 201);
    }

    protected function transform(MemoryWallItem $item): array
    {
        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'type' => $item->type,
            'title' => $item->title,
            'body' => $item->body,
            'scripture_ref' => $item->scripture_ref,
            'starts_on' => optional($item->starts_on)->toDateString(),
            'ends_on' => optional($item->ends_on)->toDateString(),
        ];
    }
}
