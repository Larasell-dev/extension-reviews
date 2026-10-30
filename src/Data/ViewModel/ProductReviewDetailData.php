<?php

namespace Larasell\Reviews\Data\ViewModel;

use Illuminate\Support\Facades\Route;
use Larasell\Reviews\Models\ProductReview;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A review as the page shows it: what the reviewer wrote, formatted to read. */
#[TypeScript]
class ProductReviewDetailData extends Data
{
    public function __construct(
        public string $name,
        public int $rating,
        public int $maxRating,
        public ?string $comment,
        public BadgeData $status,
        public ?string $submittedAt,
        public LinkData $product,
        /** Null for a guest review. */
        public ?LinkData $customer,
    ) {}

    public static function fromModel(ProductReview $review): self
    {
        return new self(
            name: $review->name,
            rating: $review->rating,
            maxRating: ProductReview::MAX_RATING,
            comment: $review->comment,
            status: new BadgeData($review->status->label(), $review->status->variant()),
            submittedAt: $review->created_at?->format('M j, Y, g:i A'),
            // Apps can turn the products and customers modules off, which removes their pages.
            product: new LinkData(
                $review->product->name->get(),
                Route::has('larasell.admin.products.edit') ? route('larasell.admin.products.edit', $review->product) : null,
            ),
            customer: $review->customer === null ? null : new LinkData(
                $review->customer->fullName(),
                Route::has('larasell.admin.customers.edit') ? route('larasell.admin.customers.edit', $review->customer) : null,
            ),
        );
    }
}
