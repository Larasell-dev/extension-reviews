<?php

namespace Larasell\Reviews\AuditRecords\ProductReviews;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Reviews\Models\ProductReview;

/**
 * A review was deleted. Keeps its rating and status, but not the reviewer's name or comment.
 *
 * @internal Recorded by DeleteProductReview. Its event name, product_review.deleted, is stable.
 */
class ProductReviewDeleted extends AuditRecord
{
    public function __construct(private readonly ProductReview $review) {}

    public function event(): string
    {
        return 'product_review.deleted';
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
            'rating' => $this->review->rating,
            'status' => $this->review->status->value,
        ];
    }
}
