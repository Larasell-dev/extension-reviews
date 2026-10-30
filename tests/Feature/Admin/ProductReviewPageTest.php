<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Larasell\Models\Customer;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;

it('shows a review with its product, customer, rating, comment and status', function () {
    signInAdmin();
    $product = createProduct();
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $review = ProductReview::create([
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'name' => 'Ada L.',
        'rating' => 4,
        'comment' => 'Bright and sturdy.',
        'status' => ProductReviewStatus::Approved,
        'created_at' => now()->setDate(2026, 10, 1)->setTime(9, 30),
    ]);

    $this->get(route('larasell.admin.product-reviews.show', $review))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('larasell-reviews::ProductReview/Show', shouldExist: false)
            ->where('review.name', 'Ada L.')
            ->where('review.rating', 4)
            ->where('review.maxRating', 5)
            ->where('review.comment', 'Bright and sturdy.')
            ->where('review.status', ['label' => 'Approved', 'variant' => 'success'])
            ->where('review.submittedAt', 'Oct 1, 2026, 9:30 AM')
            ->where('review.product', ['name' => 'Desk lamp', 'url' => route('larasell.admin.products.edit', $product)])
            ->where('review.customer', ['name' => 'Ada Lovelace', 'url' => route('larasell.admin.customers.edit', $customer)])
            ->where('indexUrl', route('larasell.admin.product-reviews.index'))
            ->where('moderation', [
                'status' => 'approved',
                'rejectUrl' => route('larasell.admin.product-reviews.rejection.store', $review),
            ]));
});

it('titles the page after the product and reviewer', function () {
    signInAdmin();
    $review = ProductReview::create(['product_id' => createProduct()->id, 'name' => 'Alex', 'rating' => 5]);

    preg_match_all('/<title\b[^>]*>(.*?)<\/title>/s', $this->get(route('larasell.admin.product-reviews.show', $review))->getContent(), $titles);

    expect(array_map(html_entity_decode(...), $titles[1]))->toBe(['Review of Desk lamp by Alex - Admin']);
});

it('offers the status changes a review can still make', function (ProductReviewStatus $status, array $actions) {
    signInAdmin();
    $review = ProductReview::create(['product_id' => createProduct()->id, 'name' => 'Alex', 'rating' => 5, 'status' => $status]);

    $urls = [
        'approveUrl' => route('larasell.admin.product-reviews.approval.store', $review),
        'rejectUrl' => route('larasell.admin.product-reviews.rejection.store', $review),
    ];

    $this->get(route('larasell.admin.product-reviews.show', $review))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('moderation', ['status' => $status->value, ...array_intersect_key($urls, array_flip($actions))]));
})->with([
    'pending' => [ProductReviewStatus::Pending, ['approveUrl', 'rejectUrl']],
    'approved' => [ProductReviewStatus::Approved, ['rejectUrl']],
    'rejected' => [ProductReviewStatus::Rejected, ['approveUrl']],
]);

it('shows a guest review without a customer link', function () {
    signInAdmin();
    $review = ProductReview::create(['product_id' => createProduct()->id, 'name' => 'Alex', 'rating' => 5]);

    $this->get(route('larasell.admin.product-reviews.show', $review))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('review.customer', null)
            ->where('review.comment', null));
});

it('answers 404 for a review that does not exist', function () {
    signInAdmin();

    $this->get(route('larasell.admin.product-reviews.show', 999))->assertNotFound();
});

it('sends guests to the login page', function () {
    $review = ProductReview::create(['product_id' => createProduct()->id, 'name' => 'Alex', 'rating' => 5]);

    $this->get(route('larasell.admin.product-reviews.show', $review))->assertRedirect(route('larasell.admin.login'));
});
