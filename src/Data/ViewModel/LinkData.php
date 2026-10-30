<?php

namespace Larasell\Reviews\Data\ViewModel;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A record's name, linking to its admin page. */
#[TypeScript]
class LinkData extends Data
{
    public function __construct(
        public string $name,
        /** Null while the module that owns the page is turned off. */
        public ?string $url,
    ) {}
}
