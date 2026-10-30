<?php

namespace Larasell\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Larasell\Larasell\Models\Customer;

/**
 * A review's customer, as `?include=customer` adds it to a review document.
 *
 * @mixin Customer
 */
class CustomerResource extends JsonApiResource
{
    public const string TYPE = 'customers';

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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }
}
