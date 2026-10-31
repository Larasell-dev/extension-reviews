<?php

namespace Larasell\Reviews\AuditRecords\ProductReviews;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;

/**
 * A review was approved, so the shop shows it.
 *
 * @internal Recorded by ApproveProductReview. Its event name, product_review.approved, is stable.
 */
class ProductReviewApproved extends AuditRecord
{
    public function __construct(private readonly ProductReview $review, private readonly ProductReviewStatus $from) {}

    public function event(): string
    {
        return 'product_review.approved';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return new Subject('product_review', $this->review->getKey());
    }

    public function properties(): array
    {
        return [
            'product_id' => $this->review->product_id,
            'status' => ['from' => $this->from->value, 'to' => ProductReviewStatus::Approved->value],
        ];
    }
}
