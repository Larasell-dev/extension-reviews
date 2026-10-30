<?php

namespace Larasell\Reviews\Actions;

use Larasell\Reviews\Events\ProductReviewUpdated;
use Larasell\Reviews\Exceptions\InvalidProductReview;
use Larasell\Reviews\Models\ProductReview;

class UpdateProductReview
{
    /**
     * Changes what the review says. Its status changes with ApproveProductReview and
     * RejectProductReview, which announce those. Announced only when something changed.
     *
     * @param  array{rating?: int, name?: string, comment?: string|null}  $attributes
     */
    public function handle(ProductReview $review, array $attributes): void
    {
        $rating = $attributes['rating'] ?? $review->rating;

        if ($rating < ProductReview::MIN_RATING || $rating > ProductReview::MAX_RATING) {
            throw InvalidProductReview::invalidRating($rating);
        }

        $review->fill(array_intersect_key($attributes, array_flip(['rating', 'name', 'comment'])));

        if ($review->isDirty()) {
            $review->save();

            ProductReviewUpdated::dispatch($review);
        }
    }
}
