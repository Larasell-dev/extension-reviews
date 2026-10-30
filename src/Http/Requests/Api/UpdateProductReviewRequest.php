<?php

namespace Larasell\Reviews\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Http\Exceptions\JsonApiError;
use Larasell\Reviews\Http\Requests\Api\Concerns\NamesDocumentMembers;
use Larasell\Reviews\Http\Resources\ProductReviewResource;
use Larasell\Reviews\Models\ProductReview;

/**
 * A JSON:API document with the review as its primary data. Every attribute is optional;
 * attributes left out stay as they are.
 */
class UpdateProductReviewRequest extends FormRequest
{
    use NamesDocumentMembers;

    /**
     * The spec answers these with their own status codes rather than 422.
     */
    protected function prepareForValidation(): void
    {
        $review = $this->route('review');
        assert($review instanceof ProductReview);

        if ($this->filled('data.type') && $this->input('data.type') !== ProductReviewResource::TYPE) {
            throw JsonApiError::conflict('This endpoint updates resources of type ['.ProductReviewResource::TYPE.'].', '/data/type');
        }

        if ($this->filled('data.id') && $this->input('data.id') !== (string) $review->getKey()) {
            throw JsonApiError::conflict("The id must match the review in the URL, [{$review->getKey()}].", '/data/id');
        }

        if ($this->has('data.relationships')) {
            throw JsonApiError::forbidden('A review\'s product and customer can\'t be changed.', '/data/relationships');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.type' => ['required', 'string'],
            'data.id' => ['required', 'string'],
            'data.attributes' => ['sometimes', 'array'],
            'data.attributes.rating' => ['sometimes', 'integer', 'between:'.ProductReview::MIN_RATING.','.ProductReview::MAX_RATING],
            'data.attributes.name' => ['sometimes', 'required', 'string', 'max:255'],
            'data.attributes.comment' => ['sometimes', 'nullable', 'string', 'max:5000'],
            // A review can be approved or rejected, but never goes back to pending.
            'data.attributes.status' => ['sometimes', Rule::in([ProductReviewStatus::Approved->value, ProductReviewStatus::Rejected->value])],
        ];
    }

    /**
     * @return array{rating?: int, name?: string, comment?: string|null, status?: string}
     */
    public function reviewAttributes(): array
    {
        /** @var array{rating?: int, name?: string, comment?: string|null, status?: string} */
        return $this->validated('data.attributes', []);
    }
}
