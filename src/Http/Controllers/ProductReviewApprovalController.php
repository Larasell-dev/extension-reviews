<?php

namespace Larasell\Reviews\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Models\ProductReview;

class ProductReviewApprovalController
{
    public function store(ProductReview $productReview, ApproveProductReview $action): RedirectResponse
    {
        $action->handle($productReview);

        return back();
    }
}
