<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Larasell\Larasell\Models\Customer;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Actions\RejectProductReview;
use Larasell\Reviews\Actions\SubmitProductReview;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewApproved;
use Larasell\Reviews\Events\ProductReviewRejected;
use Larasell\Reviews\Events\ProductReviewSubmitted;
use Larasell\Reviews\Exceptions\InvalidProductReview;
use Larasell\Reviews\Models\ProductReview;

it('submits a pending review for a product', function () {
    Event::fake();
    $product = createProduct();

    $review = app(SubmitProductReview::class)->handle($product, rating: 4, name: 'Alex', comment: 'Bright and sturdy.');

    expect($review->fresh())
        ->product->is($product)->toBeTrue()
        ->customer->toBeNull()
        ->name->toBe('Alex')
        ->rating->toBe(4)
        ->comment->toBe('Bright and sturdy.')
        ->status->toBe(ProductReviewStatus::Pending);

    Event::assertDispatchedTimes(ProductReviewSubmitted::class, 1);
    Event::assertDispatched(ProductReviewSubmitted::class, fn (ProductReviewSubmitted $event) => $event->review->is($review));
});

it('submits a review without a comment', function () {
    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, name: 'Alex');

    expect($review->fresh()->comment)->toBeNull();
});

it('accepts ratings from one to five stars', function (int $rating) {
    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: $rating, name: 'Alex');

    expect($review->fresh()->rating)->toBe($rating);
})->with([1, 5]);

it('rejects ratings outside one to five stars', function (int $rating) {
    Event::fake();

    expect(fn () => app(SubmitProductReview::class)->handle(createProduct(), rating: $rating, name: 'Alex'))
        ->toThrow(function (InvalidProductReview $exception) {
            expect($exception->errorCode())->toBe('larasell-reviews.review.invalid-rating')
                ->and($exception->getMessage())->toContain('between 1 and 5');
        });

    expect(ProductReview::count())->toBe(0);
    Event::assertNotDispatched(ProductReviewSubmitted::class);
})->with([0, 6]);

it('links a customer and copies their name onto the review', function () {
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, customer: $customer);

    expect($review->fresh())
        ->customer->is($customer)->toBeTrue()
        ->name->toBe('Ada Lovelace');
});

it('prefers the given name over the linked customer\'s', function () {
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, name: 'Ada L.', customer: $customer);

    expect($review->fresh()->name)->toBe('Ada L.');
});

it('needs a name when no customer is linked', function () {
    expect(fn () => app(SubmitProductReview::class)->handle(createProduct(), rating: 5))
        ->toThrow(function (InvalidProductReview $exception) {
            expect($exception->errorCode())->toBe('larasell-reviews.review.missing-name');
        });
});

it('keeps the review and its name when the customer is deleted', function () {
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, customer: $customer);

    $customer->delete();

    expect($review->fresh())
        ->customer_id->toBeNull()
        ->name->toBe('Ada Lovelace');
});

it('deletes the reviews of a deleted product', function () {
    $product = createProduct();
    app(SubmitProductReview::class)->handle($product, rating: 5, name: 'Alex');

    $product->delete();

    expect(ProductReview::count())->toBe(0);
});

it('approves a review', function (ProductReviewStatus $from) {
    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, name: 'Alex');
    $review->update(['status' => $from]);
    Event::fake();

    app(ApproveProductReview::class)->handle($review);

    expect($review->fresh()->status)->toBe(ProductReviewStatus::Approved);
    Event::assertDispatchedTimes(ProductReviewApproved::class, 1);
    Event::assertDispatched(ProductReviewApproved::class, fn (ProductReviewApproved $event) => $event->review->is($review));
    Event::assertNotDispatched(ProductReviewRejected::class);
})->with([ProductReviewStatus::Pending, ProductReviewStatus::Rejected]);

it('rejects a review', function (ProductReviewStatus $from) {
    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, name: 'Alex');
    $review->update(['status' => $from]);
    Event::fake();

    app(RejectProductReview::class)->handle($review);

    expect($review->fresh()->status)->toBe(ProductReviewStatus::Rejected);
    Event::assertDispatchedTimes(ProductReviewRejected::class, 1);
    Event::assertDispatched(ProductReviewRejected::class, fn (ProductReviewRejected $event) => $event->review->is($review));
    Event::assertNotDispatched(ProductReviewApproved::class);
})->with([ProductReviewStatus::Pending, ProductReviewStatus::Approved]);

it('does not announce a review again when it already has that status', function () {
    $review = app(SubmitProductReview::class)->handle(createProduct(), rating: 5, name: 'Alex');
    app(ApproveProductReview::class)->handle($review);
    $rejected = app(SubmitProductReview::class)->handle(createProduct('Desk chair'), rating: 1, name: 'Sam');
    app(RejectProductReview::class)->handle($rejected);
    Event::fake();

    app(ApproveProductReview::class)->handle($review);
    app(RejectProductReview::class)->handle($rejected);

    Event::assertNotDispatched(ProductReviewApproved::class);
    Event::assertNotDispatched(ProductReviewRejected::class);
});
