<?php

namespace Larasell\Reviews\Data\ViewModel\Moderation;

use Larasell\Reviews\Enums\ProductReviewStatus;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ApprovedModerationData extends ModerationData
{
    #[LiteralTypeScriptType('typeof import("../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus").Approved')]
    public ProductReviewStatus $status = ProductReviewStatus::Approved;

    public function __construct(
        public string $rejectUrl,
    ) {}
}
