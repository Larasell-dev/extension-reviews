<?php

namespace Larasell\Reviews\Http\Requests\Api\Concerns;

use Illuminate\Support\Str;

/**
 * Names each member of the JSON:API document in validation messages the way a client knows it:
 * "rating" for data.attributes.rating, "product id" for data.relationships.product.data.id.
 * The error's source.pointer still says exactly where the member is.
 */
trait NamesDocumentMembers
{
    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(array_keys($this->rules()))
            ->mapWithKeys(fn (string $key): array => [$key => Str::of($key)
                ->replaceStart('data.', '')
                ->replace(['attributes.', 'relationships.', '.data'], '')
                ->replace('.', ' ')
                ->value()])
            ->all();
    }
}
