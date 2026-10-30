<?php

namespace Larasell\Reviews\Actions;

use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewRejected;
use Larasell\Reviews\Models\ProductReview;

class RejectProductReview
{
    /**
     * Marks the review as rejected. A review that already is stays as it was and isn't announced again.
     */
    public function handle(ProductReview $review): void
    {
        // One conditional update, so two admins doing this at once announce it once.
        $changed = ProductReview::query()
            ->whereKey($review->getKey())
            ->where('status', '!=', ProductReviewStatus::Rejected)
            ->update(['status' => ProductReviewStatus::Rejected]);

        $review->refresh();

        if ($changed > 0) {
            ProductReviewRejected::dispatch($review);
        }
    }
}
