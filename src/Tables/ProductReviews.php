<?php

namespace Larasell\Reviews\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;

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

    public function emptyState(): array
    {
        return [
            'title' => 'No reviews yet',
            'description' => 'Reviews appear here once customers rate your products.',
            'icon' => Icon::Star,
        ];
    }
}
