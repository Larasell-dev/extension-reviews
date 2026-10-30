<?php

namespace Larasell\Reviews\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\ModelRegistry;
use Larasell\Larasell\Models\Product;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Actions\RejectProductReview;
use Larasell\Reviews\Actions\SubmitProductReview;
use Larasell\Reviews\Actions\UpdateProductReview;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Http\Requests\Api\StoreProductReviewRequest;
use Larasell\Reviews\Http\Requests\Api\UpdateProductReviewRequest;
use Larasell\Reviews\Http\Resources\ProductReviewResource;
use Larasell\Reviews\Models\ProductReview;

class ProductReviewController
{
    private const int DEFAULT_PAGE_SIZE = 25;

    private const int MAX_PAGE_SIZE = 100;

    /**
     * Pages with JSON:API's page[number] and page[size] query parameters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $size = min(max($request->integer('page.size', self::DEFAULT_PAGE_SIZE), 1), self::MAX_PAGE_SIZE);
        $number = max($request->integer('page.number', 1), 1);

        $reviews = ProductReview::query()
            ->latest()
            // Reviews submitted in the same second keep a stable order across pages.
            ->orderByDesc('id')
            ->paginate($size, pageName: 'page[number]', page: $number)
            ->appends('page[size]', (string) $size);

        return ProductReviewResource::collection($reviews);
    }

    public function store(StoreProductReviewRequest $request, SubmitProductReview $submit, ModelRegistry $models): ProductReviewResource
    {
        /** @var Product $product */
        $product = $models->product->class()::query()->findOrFail($request->input('data.relationships.product.data.id'));
        /** @var Customer|null $customer */
        $customer = $request->filled('data.relationships.customer.data.id')
            ? $models->customer->class()::query()->findOrFail($request->input('data.relationships.customer.data.id'))
            : null;

        return new ProductReviewResource($submit->handle(
            $product,
            rating: $request->integer('data.attributes.rating'),
            name: $request->string('data.attributes.name')->value() ?: null,
            comment: $request->input('data.attributes.comment'),
            customer: $customer,
        ));
    }

    public function show(ProductReview $review): ProductReviewResource
    {
        return new ProductReviewResource($review);
    }

    public function update(UpdateProductReviewRequest $request, ProductReview $review, UpdateProductReview $update, ApproveProductReview $approve, RejectProductReview $reject): ProductReviewResource
    {
        $validated = $request->reviewAttributes();

        $update->handle($review, $validated);

        match (isset($validated['status']) ? ProductReviewStatus::from($validated['status']) : null) {
            ProductReviewStatus::Approved => $approve->handle($review),
            ProductReviewStatus::Rejected => $reject->handle($review),
            default => null,
        };

        return new ProductReviewResource($review);
    }
}
