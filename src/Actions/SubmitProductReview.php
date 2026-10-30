<?php

namespace Larasell\Reviews\Actions;

use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Product;
use Larasell\Reviews\Events\ProductReviewSubmitted;
use Larasell\Reviews\Exceptions\InvalidProductReview;
use Larasell\Reviews\Models\ProductReview;

class SubmitProductReview
{
    /**
     * Adds a pending review to the product. A linked customer's name is copied onto the review,
     * unless a name is given, so the review keeps it when the customer is deleted.
     */
    public function handle(Product $product, int $rating, ?string $name = null, ?string $comment = null, ?Customer $customer = null): ProductReview
    {
        if ($rating < ProductReview::MIN_RATING || $rating > ProductReview::MAX_RATING) {
            throw InvalidProductReview::invalidRating($rating);
        }

        $name ??= $customer?->fullName() ?? throw InvalidProductReview::missingName();

        $review = ProductReview::create([
            'product_id' => $product->getKey(),
            'customer_id' => $customer?->getKey(),
            'name' => $name,
            'rating' => $rating,
            'comment' => $comment,
        ]);

        ProductReviewSubmitted::dispatch($review);

        return $review;
    }
}
