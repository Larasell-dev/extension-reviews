<?php

namespace Larasell\Reviews\Actions;

use Larasell\Reviews\Events\ProductReviewDeleted;
use Larasell\Reviews\Models\ProductReview;

class DeleteProductReview
{
    public function handle(ProductReview $review): void
    {
        $review->delete();

        ProductReviewDeleted::dispatch($review);
    }
}
