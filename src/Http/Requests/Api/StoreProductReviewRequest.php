<?php

namespace Larasell\Reviews\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Models\ModelRegistry;
use Larasell\Reviews\Http\Exceptions\JsonApiError;
use Larasell\Reviews\Http\Requests\Api\Concerns\NamesDocumentMembers;
use Larasell\Reviews\Http\Resources\CustomerResource;
use Larasell\Reviews\Http\Resources\ProductResource;
use Larasell\Reviews\Http\Resources\ProductReviewResource;
use Larasell\Reviews\Models\ProductReview;

/**
 * A JSON:API document with a new product review as its primary data:
 * data.attributes holds what the review says, data.relationships its product and customer.
 */
class StoreProductReviewRequest extends FormRequest
{
    use NamesDocumentMembers;

    /**
     * The spec answers these with their own status codes rather than 422.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('data.id')) {
            throw JsonApiError::forbidden('Product reviews get their id from the server.', '/data/id');
        }

        if ($this->filled('data.type') && $this->input('data.type') !== ProductReviewResource::TYPE) {
            throw JsonApiError::conflict('This endpoint creates resources of type ['.ProductReviewResource::TYPE.'].', '/data/type');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $models = app(ModelRegistry::class);

        return [
            'data' => ['required', 'array'],
            'data.type' => ['required', 'string'],
            'data.attributes' => ['required', 'array'],
            'data.attributes.rating' => ['required', 'integer', 'between:'.ProductReview::MIN_RATING.','.ProductReview::MAX_RATING],
            // A linked customer's name is copied onto the review when none is given.
            'data.attributes.name' => ['required_without:data.relationships.customer.data', 'nullable', 'string', 'max:255'],
            'data.attributes.comment' => ['nullable', 'string', 'max:5000'],
            'data.relationships.product.data' => ['required', 'array'],
            'data.relationships.product.data.type' => ['required', Rule::in([ProductResource::TYPE])],
            'data.relationships.product.data.id' => ['required', 'string', Rule::exists($models->product->class(), 'id')],
            'data.relationships.customer.data' => ['nullable', 'array'],
            'data.relationships.customer.data.type' => ['required_with:data.relationships.customer.data', Rule::in([CustomerResource::TYPE])],
            'data.relationships.customer.data.id' => ['required_with:data.relationships.customer.data', 'string', Rule::exists($models->customer->class(), 'id')],
        ];
    }
}
