<?php

namespace Larasell\Reviews\Data\View;

use Larasell\Reviews\Data\ViewModel\Moderation\ModerationData;
use Larasell\Reviews\Data\ViewModel\ProductReviewDetailData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductReviewPageData extends Data
{
    public function __construct(
        public ProductReviewDetailData $review,
        public string $indexUrl,
        /** The status changes the review can still make, told apart by its status. */
        #[LiteralTypeScriptType(
            'Larasell.Reviews.Data.ViewModel.Moderation.PendingModerationData'
            .' | Larasell.Reviews.Data.ViewModel.Moderation.ApprovedModerationData'
            .' | Larasell.Reviews.Data.ViewModel.Moderation.RejectedModerationData'
        )]
        public ModerationData $moderation,
    ) {}
}
