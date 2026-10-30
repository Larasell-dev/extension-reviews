<?php

namespace Larasell\Reviews\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function show(ProductReview $review): ProductReviewResource
    {
        return new ProductReviewResource($review);
    }
}
