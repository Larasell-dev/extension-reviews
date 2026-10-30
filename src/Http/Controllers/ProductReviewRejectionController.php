<?php

namespace Larasell\Reviews\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Reviews\Actions\RejectProductReview;
use Larasell\Reviews\Models\ProductReview;

class ProductReviewRejectionController
{
    public function store(ProductReview $productReview, RejectProductReview $action): RedirectResponse
    {
        $action->handle($productReview);

        return back();
    }
}
