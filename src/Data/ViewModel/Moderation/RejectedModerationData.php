<?php

namespace Larasell\Reviews\Data\ViewModel\Moderation;

use Larasell\Reviews\Enums\ProductReviewStatus;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RejectedModerationData extends ModerationData
{
    #[LiteralTypeScriptType('typeof import("../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus").Rejected')]
    public ProductReviewStatus $status = ProductReviewStatus::Rejected;

    public function __construct(
        public string $approveUrl,
    ) {}
}
