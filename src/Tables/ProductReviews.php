<?php

namespace Larasell\Reviews\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Actions\RejectProductReview;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;
use LogicException;

class ProductReviews extends Table
{
    /**
     * @return Builder<ProductReview>
     */
    protected function model(): Builder
    {
        return ProductReview::query()->with('product')->latest();
    }

    public function columns(): array
    {
        return [
            TextColumn::make('product')->map(fn (mixed $value, ProductReview $review): string => $review->product->name->get()),
            TextColumn::make('name', 'Customer'),
            TextColumn::make('rating')->map(fn (int $rating): string => str_repeat('★', $rating).str_repeat('☆', ProductReview::MAX_RATING - $rating)),
            TextColumn::make('comment')->map(fn (?string $comment): ?string => $comment === null ? null : Str::limit($comment, 80)),
            BadgeColumn::make('status')
                ->map(fn (ProductReviewStatus $status): string => $status->label())
                ->variant(fn (ProductReviewStatus $status): Variant => $status->variant()),
            TextColumn::make('created_at', 'Date')->map(fn (?Carbon $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Approve', handle: fn (ProductReview $review) => app(ApproveProductReview::class)->handle($review))
                ->hidden(fn (ProductReview $review): bool => $review->status === ProductReviewStatus::Approved)
                ->icon(Icon::Check),
            Action::make('Reject', handle: fn (ProductReview $review) => app(RejectProductReview::class)->handle($review))
                ->hidden(fn (ProductReview $review): bool => $review->status === ProductReviewStatus::Rejected)
                ->icon(Icon::Close),
        ];
    }

    /**
     * The panel only runs actions of tables in the app's tables namespace, so this
     * package's actions post to its own routes instead.
     */
    public function actionUrl(Action $action, Model $record): string
    {
        return match ($action->name()) {
            'approve' => route('larasell.admin.product-reviews.approval.store', $record),
            'reject' => route('larasell.admin.product-reviews.rejection.store', $record),
            default => throw new LogicException("Action [{$action->name()}] has no route."),
        };
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No reviews yet',
            'description' => 'Reviews appear here once customers rate your products.',
            'icon' => Icon::Star,
        ];
    }
}
