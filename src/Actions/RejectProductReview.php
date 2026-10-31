<?php

namespace Larasell\Reviews\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Audit\Actor;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\UnknownActor;
use Larasell\Reviews\AuditRecords\ProductReviews\ProductReviewRejected as ProductReviewRejectedRecord;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewRejected;
use Larasell\Reviews\Models\ProductReview;

class RejectProductReview
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Marks the review as rejected. A review that already is stays as it was and isn't announced
     * or audited again. Nothing changes if the change can't be audited.
     *
     * The actor is who the audit log records: the signed-in admin, the API token, or a
     * SystemActor for jobs and commands.
     */
    public function handle(ProductReview $review, Actor|UnknownActor $actor): void
    {
        $changed = DB::transaction(function () use ($review, $actor): bool {
            // Locked, so the status it had is the one this replaced.
            /** @var ProductReviewStatus $from */
            $from = ProductReview::query()->whereKey($review->getKey())->lockForUpdate()->value('status');

            // One conditional update, so two admins doing this at once announce it once.
            $changed = ProductReview::query()
                ->whereKey($review->getKey())
                ->where('status', '!=', ProductReviewStatus::Rejected)
                ->update(['status' => ProductReviewStatus::Rejected]);

            if ($changed > 0) {
                $this->auditor->record(new ProductReviewRejectedRecord($review, $from), $actor);
            }

            return $changed > 0;
        });

        $review->refresh();

        if ($changed) {
            ProductReviewRejected::dispatch($review);
        }
    }
}
