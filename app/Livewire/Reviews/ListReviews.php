<?php

namespace App\Livewire\Reviews;

use App\Models\BusinessReview;
use App\Models\ProductReview;
use App\Services\ReviewAggregateService;
use App\Support\StationeryHub;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListReviews extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $hub = StationeryHub::KIDZ_MART;

    public function mount(string $hub = StationeryHub::KIDZ_MART): void
    {
        $this->hub = $hub;
    }

    public function table(Table $table): Table
    {
        $isSuperAdmin = (int) Auth::user()->business_id === 1;

        $columns = [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('M d, Y H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->badge()
                ->color(fn (string $state): string => $state === 'Shop' ? 'info' : 'success'),
            Tables\Columns\TextColumn::make('subject')
                ->label('Product / Shop')
                ->searchable(),
            Tables\Columns\TextColumn::make('reviewer_name')
                ->label('Customer')
                ->searchable(),
            Tables\Columns\TextColumn::make('rating')
                ->label('Rating')
                ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                ->sortable(),
            Tables\Columns\TextColumn::make('comment')
                ->label('Feedback')
                ->limit(60)
                ->wrap(),
            Tables\Columns\IconColumn::make('verified_purchase')
                ->label('Verified')
                ->boolean(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'hidden' => 'gray',
                    'rejected' => 'danger',
                    default => 'warning',
                }),
        ];

        if ($isSuperAdmin) {
            $columns[] = Tables\Columns\TextColumn::make('business_name')
                ->label('Business')
                ->searchable();
        }

        return $table
            ->query($this->reviewsQuery())
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'product' => 'Product',
                        'business' => 'Shop',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'hidden' => 'Hidden',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Action::make('hide')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn (array $record): bool => $record['status'] === 'approved')
                    ->requiresConfirmation()
                    ->action(fn (array $record) => $this->updateReviewStatus($record, 'hidden')),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (array $record): bool => $record['status'] !== 'approved')
                    ->action(fn (array $record) => $this->updateReviewStatus($record, 'approved')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No customer feedback yet')
            ->emptyStateDescription('Product and shop ratings from customers will appear here.');
    }

    protected function reviewsQuery(): Builder
    {
        $businessId = (int) Auth::user()->business_id;

        $productReviews = ProductReview::query()
            ->selectRaw("
                product_reviews.id,
                product_reviews.uuid,
                product_reviews.created_at,
                product_reviews.reviewer_name,
                product_reviews.rating,
                product_reviews.comment,
                product_reviews.verified_purchase,
                product_reviews.status,
                product_reviews.business_id,
                'product' as review_type,
                products.name as subject,
                businesses.name as business_name
            ")
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->join('businesses', 'businesses.id', '=', 'product_reviews.business_id')
            ->where('product_reviews.hub', $this->hub);

        $businessReviews = BusinessReview::query()
            ->selectRaw("
                business_reviews.id,
                business_reviews.uuid,
                business_reviews.created_at,
                business_reviews.reviewer_name,
                business_reviews.rating,
                business_reviews.comment,
                business_reviews.verified_purchase,
                business_reviews.status,
                business_reviews.business_id,
                'business' as review_type,
                businesses.name as subject,
                businesses.name as business_name
            ")
            ->join('businesses', 'businesses.id', '=', 'business_reviews.business_id')
            ->where('business_reviews.hub', $this->hub);

        if ($businessId !== 1) {
            $productReviews->where('product_reviews.business_id', $businessId);
            $businessReviews->where('business_reviews.business_id', $businessId);
        }

        $union = $productReviews->unionAll($businessReviews);

        return ProductReview::query()
            ->fromSub($union, 'marketplace_reviews')
            ->selectRaw("
                id,
                uuid,
                created_at,
                reviewer_name,
                rating,
                comment,
                verified_purchase,
                status,
                business_id,
                review_type,
                subject,
                business_name,
                CASE WHEN review_type = 'business' THEN 'Shop' ELSE 'Product' END as type
            ");
    }

    protected function updateReviewStatus(array $record, string $status): void
    {
        if ($record['review_type'] === 'business') {
            $review = BusinessReview::find($record['id']);
            if ($review && $this->canManageReview($review->business_id)) {
                $review->update(['status' => $status]);
                app(ReviewAggregateService::class)->refreshBusinessRating($review->business_id);
            }
        } else {
            $review = ProductReview::find($record['id']);
            if ($review && $this->canManageReview($review->business_id)) {
                $review->update(['status' => $status]);
                app(ReviewAggregateService::class)->refreshProductRating($review->product_id);
            }
        }

        Notification::make()
            ->title('Review updated')
            ->success()
            ->send();
    }

    protected function canManageReview(int $businessId): bool
    {
        $userBusinessId = (int) Auth::user()->business_id;

        return $userBusinessId === 1 || $userBusinessId === $businessId;
    }

    public function render(): View
    {
        return view('livewire.reviews.list-reviews');
    }
}
