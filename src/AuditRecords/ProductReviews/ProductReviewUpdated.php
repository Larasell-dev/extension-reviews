<?php

namespace Larasell\Reviews\AuditRecords\ProductReviews;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Reviews\Models\ProductReview;

/**
 * What a review says was changed. Which fields changed, and the rating before and after, but not
 * the reviewer's name or comment: those are the customer's words, not evidence of the change.
 *
 * @internal Recorded by UpdateProductReview. Its event name, product_review.updated, is stable.
 */
class ProductReviewUpdated extends AuditRecord
{
    /**
     * @var list<string>
     */
    private readonly array $changed;

    private readonly int $ratingFrom;

    /**
     * Takes the review before it is saved, while it still knows what changed.
     */
    public function __construct(private readonly ProductReview $review)
    {
        $this->changed = array_values(array_intersect(['rating', 'name', 'comment'], array_keys($review->getDirty())));
        $this->ratingFrom = $review->getOriginal('rating');
    }

    public function event(): string
    {
        return 'product_review.updated';
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
            'changed' => $this->changed,
            ...in_array('rating', $this->changed, true) ? ['rating' => ['from' => $this->ratingFrom, 'to' => $this->review->rating]] : [],
        ];
    }
}
