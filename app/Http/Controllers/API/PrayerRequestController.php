<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Http\Request;

class PrayerRequestController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = PrayerRequest::query()
            ->with(['student:id,first_name,last_name'])
            ->where('business_id', $businessId)
            ->latest();

        if ($user instanceof ParentGuardian) {
            $query->where('parent_guardian_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $items = $query->paginate(min((int) $request->query('per_page', 30), 50));
        $isStaff = $user instanceof User;

        $items->getCollection()->transform(function (PrayerRequest $item) use ($isStaff) {
            return $this->transform($item, $isStaff);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'prayer_requests' => $items->items(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'student_id' => 'nullable|exists:students,id',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $item = PrayerRequest::create([
            'business_id' => $businessId,
            'parent_guardian_id' => $user instanceof ParentGuardian ? $user->id : null,
            'student_id' => $validated['student_id'] ?? null,
            'body' => $validated['body'],
            'is_anonymous' => (bool) ($validated['is_anonymous'] ?? false),
            'status' => 'received',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prayer request received.',
            'data' => ['prayer_request' => $this->transform($item, $user instanceof User)],
        ], 201);
    }

    public function update(Request $request, PrayerRequest $prayerRequest)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User || (int) $prayerRequest->business_id !== (int) $request->get('business_id')) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:received,being_prayed_for,answered',
            'praise_report' => 'nullable|string|max:2000',
        ]);

        $prayerRequest->update($validated);

        return response()->json([
            'success' => true,
            'data' => ['prayer_request' => $this->transform($prayerRequest->fresh(), true)],
        ]);
    }

    protected function transform(PrayerRequest $item, bool $isStaff): array
    {
        $anonymous = $item->is_anonymous && ! $isStaff;

        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'body' => $item->body,
            'status' => $item->status,
            'status_label' => match ($item->status) {
                'being_prayed_for' => 'Being prayed for',
                'answered' => 'Answered',
                default => 'Received',
            },
            'is_anonymous' => (bool) $item->is_anonymous,
            'praise_report' => $item->praise_report,
            'student_name' => $anonymous ? null : $item->student?->full_name,
            'created_at' => optional($item->created_at)->toIso8601String(),
        ];
    }
}
