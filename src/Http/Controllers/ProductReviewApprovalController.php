<?php

namespace Larasell\Reviews\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Models\Admin;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Models\ProductReview;

class ProductReviewApprovalController
{
    public function store(ProductReview $productReview, ApproveProductReview $action, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        $action->handle($productReview, $admin);

        return back();
    }
}
