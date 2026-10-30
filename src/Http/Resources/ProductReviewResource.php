<?php

namespace Larasell\Reviews\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Larasell\Reviews\Models\ProductReview;

/**
 * @mixin ProductReview
 */
class ProductReviewResource extends JsonApiResource
{
    public const string TYPE = 'product-reviews';

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
            'name' => $this->name,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, class-string<JsonApiResource>>
     */
    public function toRelationships(Request $request): array
    {
        return [
            'product' => ProductResource::class,
            'customer' => CustomerResource::class,
        ];
    }

    /**
     * Laravel only adds the relationships a client includes. The product and customer are always
     * linked, read from the foreign keys without a query, so clients see them without ?include=.
     *
     * @return array<string, mixed>
     */
    protected function resolveResourceRelationshipIdentifiers(JsonApiRequest $request): array
    {
        return [
            'product' => ['data' => ['id' => (string) $this->product_id, 'type' => ProductResource::TYPE]],
            'customer' => ['data' => $this->customer_id === null ? null : ['id' => (string) $this->customer_id, 'type' => CustomerResource::TYPE]],
            ...parent::resolveResourceRelationshipIdentifiers($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toLinks(Request $request): array
    {
        return [
            'self' => route('larasell.reviews.api.show', $this->resource),
        ];
    }

    /**
     * When the review is the document's primary data, the document links to it too.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function with($request): array
    {
        return [
            ...parent::with($request),
            'links' => ['self' => route('larasell.reviews.api.show', $this->resource)],
        ];
    }

    /**
     * A 201 Created names the new review's URL, as the spec asks.
     */
    public function withResponse(Request $request, JsonResponse $response): void
    {
        parent::withResponse($request, $response);

        if ($this->resource->wasRecentlyCreated) {
            $response->headers->set('Location', route('larasell.reviews.api.show', $this->resource));
        }
    }
}
