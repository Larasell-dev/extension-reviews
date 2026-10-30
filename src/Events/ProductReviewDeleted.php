<?php

namespace Larasell\Reviews\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Larasell\Reviews\Models\ProductReview;

/**
 * Carries the review as it was. Unlike the other events it doesn't serialize the model by key,
 * since a queued listener could no longer load the deleted row.
 */
class ProductReviewDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly ProductReview $review) {}
}
