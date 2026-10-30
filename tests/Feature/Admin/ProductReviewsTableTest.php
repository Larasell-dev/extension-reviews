<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;

function createReview(array $attributes = []): ProductReview
{
    return ProductReview::create([
        'product_id' => createProduct()->id,
        'name' => 'Alex',
        'rating' => 5,
        ...$attributes,
    ]);
}

it('lists reviews with their product, customer, rating, comment and status, newest first', function () {
    signInAdmin();
    createReview(['name' => 'Alex', 'rating' => 4, 'comment' => 'Bright and sturdy.', 'status' => ProductReviewStatus::Approved, 'created_at' => now()->subDays(2)]);
    createReview(['product_id' => createProduct('Desk chair')->id, 'name' => 'Sam', 'rating' => 1]);

    $this->get(route('larasell.admin.product-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.title', 'Product reviews')
            ->where('table.columns', [
                ['name' => 'product', 'label' => 'Product', 'type' => 'text'],
                ['name' => 'name', 'label' => 'Customer', 'type' => 'text'],
                ['name' => 'rating', 'label' => 'Rating', 'type' => 'text'],
                ['name' => 'comment', 'label' => 'Comment', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['name' => 'created_at', 'label' => 'Date', 'type' => 'text'],
            ])
            ->where('table.pagination.total', 2)
            ->where('table.rows.0.product', 'Desk chair')
            ->where('table.rows.0.name', 'Sam')
            ->where('table.rows.0.rating', '★☆☆☆☆')
            ->where('table.rows.0.comment', null)
            ->where('table.rows.0.status', 'Pending')
            ->where('table.rows.1.product', 'Desk lamp')
            ->where('table.rows.1.name', 'Alex')
            ->where('table.rows.1.rating', '★★★★☆')
            ->where('table.rows.1.comment', 'Bright and sturdy.')
            ->where('table.rows.1.status', 'Approved')
            ->where('table.rows.1.created_at', '2 days ago'));
});

it('colours each review status', function (ProductReviewStatus $status, string $label, string $variant) {
    signInAdmin();
    createReview(['status' => $status]);

    $this->get(route('larasell.admin.product-reviews.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0.status', $label)
            ->where('table.rows.0._variants.status', $variant));
})->with([
    'pending' => [ProductReviewStatus::Pending, 'Pending', 'warning'],
    'approved' => [ProductReviewStatus::Approved, 'Approved', 'success'],
    'rejected' => [ProductReviewStatus::Rejected, 'Rejected', 'danger'],
]);

it('shows an empty state without reviews', function () {
    signInAdmin();

    $this->get(route('larasell.admin.product-reviews.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.emptyState.title', 'No reviews yet')
            ->where('table.rows', []));
});

it('nests the reviews page under products, which still links to the products page', function () {
    signInAdmin();

    $this->get(route('larasell.admin.product-reviews.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation.1.id', 'larasell.admin.products.index')
            ->where('navigation.1.url', route('larasell.admin.products.index'))
            ->where('navigation.1.children', [[
                'id' => 'larasell.admin.product-reviews.index',
                'label' => 'Reviews',
                'url' => route('larasell.admin.product-reviews.index'),
                'icon' => 'Star',
                'children' => [],
            ]])
            ->where('navigation.2.id', 'larasell.admin.categories.index'));
});

it('sends guests to the login page', function () {
    $this->get(route('larasell.admin.product-reviews.index'))->assertRedirect(route('larasell.admin.login'));
});
