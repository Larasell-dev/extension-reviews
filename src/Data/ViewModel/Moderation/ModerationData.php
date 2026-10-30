<?php

namespace Larasell\Reviews\Data\ViewModel\Moderation;

use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;
use Spatie\LaravelData\Data;

/**
 * The status changes a review can still make. Each status has its own subclass, so the
 * page's types only offer the urls that status has: an approved review can't be approved again.
 */
abstract class ModerationData extends Data
{
    public static function fromModel(ProductReview $review): self
    {
        $approveUrl = route('larasell.admin.product-reviews.approval.store', $review);
        $rejectUrl = route('larasell.admin.product-reviews.rejection.store', $review);

        return match ($review->status) {
            ProductReviewStatus::Pending => new PendingModerationData($approveUrl, $rejectUrl),
            ProductReviewStatus::Approved => new ApprovedModerationData($rejectUrl),
            ProductReviewStatus::Rejected => new RejectedModerationData($approveUrl),
        };
    }
}
