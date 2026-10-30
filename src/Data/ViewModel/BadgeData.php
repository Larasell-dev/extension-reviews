<?php

namespace Larasell\Reviews\Data\ViewModel;

use Larasell\Admin\Enums\Table\Variant;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A status as the page shows it: its text and the colour of its badge. */
#[TypeScript]
class BadgeData extends Data
{
    public function __construct(
        public string $label,
        #[LiteralTypeScriptType('import("@larasell-dev/ui").BadgeVariant')]
        public Variant $variant,
    ) {}
}
