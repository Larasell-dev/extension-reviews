<?php

namespace Larasell\Reviews\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Audit\Actor;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\UnknownActor;
use Larasell\Reviews\AuditRecords\ProductReviews\ProductReviewUpdated as ProductReviewUpdatedRecord;
use Larasell\Reviews\Events\ProductReviewUpdated;
use Larasell\Reviews\Exceptions\InvalidProductReview;
use Larasell\Reviews\Models\ProductReview;

class UpdateProductReview
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Changes what the review says. Its status changes with ApproveProductReview and
     * RejectProductReview, which announce those. Announced and audited only when something
     * changed. Nothing changes if the change can't be audited.
     *
     * The actor is who the audit log records: the signed-in admin, the API token, or a
     * SystemActor for jobs and commands.
     *
     * @param  array{rating?: int, name?: string, comment?: string|null}  $attributes
     */
    public function handle(ProductReview $review, array $attributes, Actor|UnknownActor $actor): void
    {
        $rating = $attributes['rating'] ?? $review->rating;

        if ($rating < ProductReview::MIN_RATING || $rating > ProductReview::MAX_RATING) {
            throw InvalidProductReview::invalidRating($rating);
        }

        $review->fill(array_intersect_key($attributes, array_flip(['rating', 'name', 'comment'])));

        if (! $review->isDirty()) {
            return;
        }

        $record = new ProductReviewUpdatedRecord($review);

        DB::transaction(function () use ($review, $record, $actor): void {
            $review->save();

            $this->auditor->record($record, $actor);
        });

        ProductReviewUpdated::dispatch($review);
    }
}
