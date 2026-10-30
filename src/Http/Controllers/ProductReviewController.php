<?php

namespace Larasell\Reviews\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Reviews\Data\View\ProductReviewPageData;
use Larasell\Reviews\Data\ViewModel\Moderation\ModerationData;
use Larasell\Reviews\Data\ViewModel\ProductReviewDetailData;
use Larasell\Reviews\Models\ProductReview;
use Laravel\Head\Facades\Head;

class ProductReviewController
{
    public function show(ProductReview $productReview): Response
    {
        $review = $productReview->load(['product', 'customer']);

        Head::title("Review of {$review->product->name->get()} by {$review->name}");

        return Inertia::render('larasell-reviews::ProductReview/Show', new ProductReviewPageData(
            review: ProductReviewDetailData::fromModel($review),
            indexUrl: route('larasell.admin.product-reviews.index'),
            moderation: ModerationData::fromModel($review),
        ));
    }
}
