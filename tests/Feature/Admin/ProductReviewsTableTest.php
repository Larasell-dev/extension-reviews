<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewApproved;
use Larasell\Reviews\Events\ProductReviewRejected;
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

/**
 * The url of a row action, read from the page like the admin panel's frontend reads it.
 */
function rowActionUrl(ProductReview $review, string $action): ?string
{
    $url = null;

    test()->get(route('larasell.admin.product-reviews.index'))
        ->assertInertia(function (AssertableInertia $page) use ($review, $action, &$url) {
            $row = collect($page->toArray()['props']['table']['rows'])->firstWhere('id', $review->id);
            $url = collect($row['_actions'])->firstWhere('name', $action)['url'] ?? null;
        });

    return $url;
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

it('offers the actions that change a review\'s status', function (ProductReviewStatus $status, array $actions) {
    signInAdmin();
    createReview(['status' => $status]);

    $this->get(route('larasell.admin.product-reviews.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0._actions', fn ($rowActions) => collect($rowActions)->pluck('name')->all() === $actions));
})->with([
    'pending' => [ProductReviewStatus::Pending, ['approve', 'reject']],
    'approved' => [ProductReviewStatus::Approved, ['reject']],
    'rejected' => [ProductReviewStatus::Rejected, ['approve']],
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

it('approves a review from its row', function () {
    signInAdmin();
    $review = createReview();
    Event::fake([ProductReviewApproved::class]);

    $this->from(route('larasell.admin.product-reviews.index'))
        ->post(rowActionUrl($review, 'approve'))
        ->assertRedirect(route('larasell.admin.product-reviews.index'));

    expect($review->fresh()->status)->toBe(ProductReviewStatus::Approved);
    Event::assertDispatchedTimes(ProductReviewApproved::class, 1);
});

it('rejects a review from its row', function () {
    signInAdmin();
    $review = createReview();
    Event::fake([ProductReviewRejected::class]);

    $this->from(route('larasell.admin.product-reviews.index'))
        ->post(rowActionUrl($review, 'reject'))
        ->assertRedirect(route('larasell.admin.product-reviews.index'));

    expect($review->fresh()->status)->toBe(ProductReviewStatus::Rejected);
    Event::assertDispatchedTimes(ProductReviewRejected::class, 1);
});

it('sends guests to the login page', function () {
    $review = createReview();

    $this->get(route('larasell.admin.product-reviews.index'))->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.product-reviews.approval.store', $review))->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.product-reviews.rejection.store', $review))->assertRedirect(route('larasell.admin.login'));

    expect($review->fresh()->status)->toBe(ProductReviewStatus::Pending);
});
