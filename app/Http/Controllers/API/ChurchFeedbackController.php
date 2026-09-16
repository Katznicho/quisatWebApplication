<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ChurchFeedback;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Http\Request;

class ChurchFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = ChurchFeedback::query()
            ->with(['student:id,first_name,last_name', 'parentGuardian:id,first_name,last_name']);

        if ($user instanceof ParentGuardian) {
            $query->where('parent_guardian_id', $user->id);
        } else {
            $query->forBusiness((int) $businessId);
        }

        $query->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $items = $query->paginate(min((int) $request->query('per_page', 30), 50));
        $isStaff = $user instanceof User;

        $items->getCollection()->transform(function (ChurchFeedback $item) use ($isStaff) {
            return $this->transform($item, $isStaff);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'feedback' => $items->items(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->get('authenticated_user');

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'student_id' => 'nullable|exists:students,id',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $item = ChurchFeedback::create([
            'business_id' => $this->resolveStoreBusinessId($request),
            'parent_guardian_id' => $user instanceof ParentGuardian ? $user->id : null,
            'student_id' => $validated['student_id'] ?? null,
            'body' => $validated['body'],
            'is_anonymous' => (bool) ($validated['is_anonymous'] ?? false),
            'status' => 'received',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback received.',
            'data' => ['feedback' => $this->transform($item, $user instanceof User)],
        ], 201);
    }

    public function update(Request $request, ChurchFeedback $churchFeedback)
    {
        $user = $request->get('authenticated_user');
        $businessId = (int) $request->get('business_id');
        $canManage = $user instanceof User && ChurchFeedback::query()
            ->forBusiness($businessId)
            ->whereKey($churchFeedback->id)
            ->exists();

        if (! $canManage) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:received,in_review,responded',
            'staff_response' => 'nullable|string|max:2000',
        ]);

        $churchFeedback->update($validated);

        return response()->json([
            'success' => true,
            'data' => ['feedback' => $this->transform($churchFeedback->fresh(), true)],
        ]);
    }

    protected function transform(ChurchFeedback $item, bool $isStaff): array
    {
        $anonymous = $item->is_anonymous && ! $isStaff;

        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'body' => $item->body,
            'status' => $item->status,
            'status_label' => match ($item->status) {
                'in_review' => 'In review',
                'responded' => 'Responded',
                default => 'Received',
            },
            'is_anonymous' => (bool) $item->is_anonymous,
            'staff_response' => $item->staff_response,
            'student_name' => $anonymous ? null : $item->student?->full_name,
            'parent_name' => $anonymous ? null : $item->parentGuardian?->full_name,
            'created_at' => optional($item->created_at)->toIso8601String(),
        ];
    }

    protected function resolveStoreBusinessId(Request $request): int
    {
        $scopedId = (int) $request->get('business_id');
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if ($business instanceof Business && $business->isChurch()) {
            return $scopedId;
        }

        if ($user instanceof ParentGuardian) {
            $church = $user->businesses()
                ->wherePivot('status', 'active')
                ->get()
                ->first(fn (Business $item) => $item->isChurch());

            if ($church) {
                return (int) $church->id;
            }
        }

        return $scopedId;
    }
}
