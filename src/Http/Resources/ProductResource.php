<?php

namespace Larasell\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Larasell\Larasell\Models\Product;

/**
 * A review's product, as `?include=product` adds it to a review document.
 *
 * @mixin Product
 */
class ProductResource extends JsonApiResource
{
    public const string TYPE = 'products';

    public function toType(Request $request): string
    {
        return self::TYPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name->get(),
            'slug' => $this->slug->get(),
        ];
    }
}
