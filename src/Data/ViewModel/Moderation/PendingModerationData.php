<?php

namespace Larasell\Reviews\Data\ViewModel\Moderation;

use Larasell\Reviews\Enums\ProductReviewStatus;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PendingModerationData extends ModerationData
{
    #[LiteralTypeScriptType('typeof import("../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus").Pending')]
    public ProductReviewStatus $status = ProductReviewStatus::Pending;

    public function __construct(
        public string $approveUrl,
        public string $rejectUrl,
    ) {}
}
