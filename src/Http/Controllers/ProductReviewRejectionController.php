<?php

namespace Larasell\Reviews\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Models\Admin;
use Larasell\Reviews\Actions\RejectProductReview;
use Larasell\Reviews\Models\ProductReview;

class ProductReviewRejectionController
{
    public function store(ProductReview $productReview, RejectProductReview $action, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        $action->handle($productReview, $admin);

        return back();
    }
}
