<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ReviewAggregateService;
use App\Support\CustomerOrderMatcher;
use App\Support\ReviewAuthor;
use App\Support\StationeryHub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductReviewController extends Controller
{
    public function index(Request $request, $id)
    {
        try {
            $product = Product::where(function (Builder $q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })->first();

            if (! $product) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
            }

            $reviews = ProductReview::query()
                ->where('product_id', $product->id)
                ->where('status', 'approved')
                ->with(['user:id,name'])
                ->orderByDesc('created_at')
                ->paginate(min((int) $request->query('per_page', 20), 50));

            $user = Auth::guard('sanctum')->user();
            $userHasReviewed = false;
            if ($user) {
                $existsQuery = ProductReview::query()
                    ->where('product_id', $product->id)
                    ->whereNull('order_item_id');
                ReviewAuthor::scopeForUser($existsQuery, $user);
                $userHasReviewed = $existsQuery->exists();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'rating' => $product->rating !== null ? (float) $product->rating : null,
                    'total_ratings' => (int) ($product->total_ratings ?? 0),
                    'reviews' => $reviews->getCollection()->map(fn (ProductReview $r) => $this->formatReview($r)),
                    'user_has_reviewed' => $userHasReviewed,
                    'can_submit_review' => $user !== null && ! $userHasReviewed,
                    'pagination' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ProductReviewController@index - '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to load reviews.'], 500);
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
            }

            $product = Product::where(function (Builder $q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })->first();

            if (! $product) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
            }

            $validated = $request->validate([
                'order_id' => 'nullable|integer',
                'order_item_id' => 'nullable|integer|required_with:order_id',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:120',
                'comment' => 'nullable|string|max:2000',
            ]);

            $authorUserId = ReviewAuthor::userId($user);
            $parentGuardianId = ReviewAuthor::parentGuardianId($user);
            $reviewerName = ReviewAuthor::displayName($user);

            if (! empty($validated['order_id'])) {
                return $this->storeVerifiedPurchaseReview(
                    $product,
                    $user,
                    $validated,
                    $authorUserId,
                    $parentGuardianId,
                    $reviewerName
                );
            }

            return $this->storeOpenReview(
                $product,
                $user,
                $validated,
                $authorUserId,
                $parentGuardianId,
                $reviewerName
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ProductReviewController@store - '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to submit review.'], 500);
        }
    }

    private function storeVerifiedPurchaseReview(
        Product $product,
        mixed $user,
        array $validated,
        ?int $authorUserId,
        ?int $parentGuardianId,
        string $reviewerName
    ) {
        $order = Order::with('items')->find($validated['order_id']);
        if (! $order || ! CustomerOrderMatcher::customerOwnsOrder($user, $order)) {
            return response()->json(['success' => false, 'message' => 'Order not found or unauthorized.'], 403);
        }

        if (! CustomerOrderMatcher::orderEligibleForReview($order)) {
            return response()->json([
                'success' => false,
                'message' => 'You can rate products after your order has been delivered or received.',
            ], 422);
        }

        /** @var OrderItem|null $orderItem */
        $orderItem = $order->items->firstWhere('id', (int) $validated['order_item_id']);
        if (! $orderItem || (int) $orderItem->product_id !== (int) $product->id) {
            return response()->json(['success' => false, 'message' => 'This product is not part of the selected order.'], 422);
        }

        $existsQuery = ProductReview::query()->where('order_item_id', $orderItem->id);
        ReviewAuthor::scopeForUser($existsQuery, $user);
        if ($existsQuery->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already reviewed this product for this order.'], 422);
        }

        $review = ProductReview::create([
            'product_id' => $product->id,
            'business_id' => $product->business_id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $authorUserId,
            'parent_guardian_id' => $parentGuardianId,
            'hub' => $order->hub ?? StationeryHub::KIDZ_MART,
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'status' => 'approved',
            'verified_purchase' => true,
            'reviewer_name' => $reviewerName,
        ]);

        app(ReviewAggregateService::class)->refreshProductRating($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your product review.',
            'data' => ['review' => $this->formatReview($review->fresh(['user:id,name']))],
        ], 201);
    }

    private function storeOpenReview(
        Product $product,
        mixed $user,
        array $validated,
        ?int $authorUserId,
        ?int $parentGuardianId,
        string $reviewerName
    ) {
        $existsQuery = ProductReview::query()
            ->where('product_id', $product->id)
            ->whereNull('order_item_id');
        ReviewAuthor::scopeForUser($existsQuery, $user);
        if ($existsQuery->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already reviewed this product.'], 422);
        }

        $review = ProductReview::create([
            'product_id' => $product->id,
            'business_id' => $product->business_id,
            'order_id' => null,
            'order_item_id' => null,
            'user_id' => $authorUserId,
            'parent_guardian_id' => $parentGuardianId,
            'hub' => $product->hub ?? StationeryHub::KIDZ_MART,
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'status' => 'approved',
            'verified_purchase' => false,
            'reviewer_name' => $reviewerName,
        ]);

        app(ReviewAggregateService::class)->refreshProductRating($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your review.',
            'data' => ['review' => $this->formatReview($review->fresh(['user:id,name']))],
        ], 201);
    }

    private function formatReview(ProductReview $review): array
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
