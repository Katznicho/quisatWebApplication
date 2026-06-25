<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessReview;
use App\Models\Order;
use App\Models\User;
use App\Services\ReviewAggregateService;
use App\Support\CustomerOrderMatcher;
use App\Support\ReviewAuthor;
use App\Support\StationeryHub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BusinessReviewController extends Controller
{
    public function index(Request $request, $id)
    {
        try {
            $business = Business::where(function (Builder $q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })->first();

            if (! $business) {
                return response()->json(['success' => false, 'message' => 'Shop not found.'], 404);
            }

            $reviews = BusinessReview::query()
                ->where('business_id', $business->id)
                ->where('status', 'approved')
                ->with(['user:id,name'])
                ->orderByDesc('created_at')
                ->paginate(min((int) $request->query('per_page', 20), 50));

            return response()->json([
                'success' => true,
                'data' => [
                    'business_id' => $business->id,
                    'business_name' => $business->name,
                    'rating' => $business->rating !== null ? (float) $business->rating : null,
                    'total_ratings' => (int) ($business->total_ratings ?? 0),
                    'reviews' => $reviews->getCollection()->map(fn (BusinessReview $r) => $this->formatReview($r)),
                    'pagination' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('BusinessReviewController@index - '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to load shop reviews.'], 500);
        }
    }

    public function store(Request $request, $id)
    {
        try {
            /** @var User|null $user */
            $user = Auth::guard('sanctum')->user();
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
            }

            $business = Business::where(function (Builder $q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })->first();

            if (! $business) {
                return response()->json(['success' => false, 'message' => 'Shop not found.'], 404);
            }

            $validated = $request->validate([
                'order_id' => 'required|integer',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:120',
                'comment' => 'nullable|string|max:2000',
            ]);

            $order = Order::find($validated['order_id']);
            if (! $order || ! CustomerOrderMatcher::customerOwnsOrder($user, $order)) {
                return response()->json(['success' => false, 'message' => 'Order not found or unauthorized.'], 403);
            }

            if ((int) $order->business_id !== (int) $business->id) {
                return response()->json(['success' => false, 'message' => 'This order does not belong to the selected shop.'], 422);
            }

            if (! CustomerOrderMatcher::orderEligibleForReview($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can rate the shop after your order has been delivered or received.',
                ], 422);
            }

            if (BusinessReview::query()
                ->where('order_id', $order->id)
                ->where(function ($q) use ($user) {
                    ReviewAuthor::scopeForUser($q, $user);
                })
                ->exists()) {
                return response()->json(['success' => false, 'message' => 'You have already reviewed this shop for this order.'], 422);
            }

            $review = BusinessReview::create([
                'business_id' => $business->id,
                'order_id' => $order->id,
                'user_id' => ReviewAuthor::userId($user),
                'parent_guardian_id' => ReviewAuthor::parentGuardianId($user),
                'hub' => $order->hub ?? StationeryHub::KIDZ_MART,
                'rating' => (int) $validated['rating'],
                'title' => $validated['title'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'status' => 'approved',
                'verified_purchase' => true,
                'reviewer_name' => ReviewAuthor::displayName($user),
            ]);

            app(ReviewAggregateService::class)->refreshBusinessRating($business->id);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your shop review.',
                'data' => ['review' => $this->formatReview($review->fresh(['user:id,name']))],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('BusinessReviewController@store - '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to submit shop review.'], 500);
        }
    }

    private function formatReview(BusinessReview $review): array
    {
        return [
            'id' => $review->id,
            'uuid' => $review->uuid,
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'comment' => $review->comment,
            'verified_purchase' => (bool) $review->verified_purchase,
            'reviewer_name' => $review->reviewer_name ?? $review->user?->name ?? 'Customer',
            'created_at' => $review->created_at?->toISOString(),
        ];
    }
}
