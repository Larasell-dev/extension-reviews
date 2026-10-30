<?php

namespace Larasell\Reviews\Actions;

use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewApproved;
use Larasell\Reviews\Models\ProductReview;

class ApproveProductReview
{
    /**
     * Marks the review as approved. A review that already is stays as it was and isn't announced again.
     */
    public function handle(ProductReview $review): void
    {
        // One conditional update, so two admins doing this at once announce it once.
        $changed = ProductReview::query()
            ->whereKey($review->getKey())
            ->where('status', '!=', ProductReviewStatus::Approved)
            ->update(['status' => ProductReviewStatus::Approved]);

        $review->refresh();

        if ($changed > 0) {
            ProductReviewApproved::dispatch($review);
        }
    }
}
