<?php

namespace Larasell\Reviews\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Audit\Actor;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\UnknownActor;
use Larasell\Reviews\AuditRecords\ProductReviews\ProductReviewDeleted as ProductReviewDeletedRecord;
use Larasell\Reviews\Events\ProductReviewDeleted;
use Larasell\Reviews\Models\ProductReview;

class DeleteProductReview
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Deletes the review. It stays if its deletion can't be audited.
     *
     * The actor is who the audit log records: the signed-in admin, the API token, or a
     * SystemActor for jobs and commands.
     */
    public function handle(ProductReview $review, Actor|UnknownActor $actor): void
    {
        DB::transaction(function () use ($review, $actor): void {
            $review->delete();

            $this->auditor->record(new ProductReviewDeletedRecord($review), $actor);
        });

        ProductReviewDeleted::dispatch($review);
    }
}
