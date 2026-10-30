<?php

declare(strict_types=1);

use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Testing\TestResponse;
use Larasell\Larasell\Models\Customer;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Models\ProductReview;

beforeEach(function () {
    // Fixed, so the timestamps in the snapshots don't change from run to run.
    $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 30));
});

/**
 * The response body as readable JSON, so snapshot diffs show what changed.
 */
function snapshotOf(TestResponse $response): string
{
    return json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

/**
 * Sends a request the way a JSON:API client does, with the JSON:API media type.
 *
 * @param  array<string, mixed>  $document
 */
function jsonApi(string $method, string $uri, array $document = [], string $contentType = 'application/vnd.api+json', string $accept = 'application/vnd.api+json'): TestResponse
{
    return test()->json($method, $uri, $document, [
        'CONTENT_TYPE' => $contentType,
        'Accept' => $accept,
    ]);
}

function apiReview(array $attributes = []): ProductReview
{
    return ProductReview::create([
        'product_id' => createProduct()->id,
        'name' => 'Alex',
        'rating' => 4,
        'comment' => 'Bright and sturdy.',
        ...$attributes,
    ]);
}

it('lists reviews as a paginated JSON:API document, newest first', function () {
    apiReview(['name' => 'Alex', 'created_at' => now()->subDay()]);
    apiReview(['name' => 'Sam', 'rating' => 2, 'comment' => null, 'status' => ProductReviewStatus::Approved]);

    $response = jsonApi('GET', route('larasell.reviews.api.index'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    expect(snapshotOf($response))->toMatchSnapshot();
});

it('pages through reviews with page[number] and page[size]', function () {
    foreach (range(1, 5) as $minutes) {
        apiReview(['name' => "Reviewer {$minutes}", 'created_at' => now()->subMinutes($minutes)]);
    }

    $response = jsonApi('GET', route('larasell.reviews.api.index', ['page' => ['number' => 2, 'size' => 2]]))->assertOk();

    expect($response->json('data.*.attributes.name'))->toBe(['Reviewer 3', 'Reviewer 4'])
        ->and(snapshotOf($response))->toMatchSnapshot();
});

it('caps the page size at 100', function () {
    apiReview();

    jsonApi('GET', route('larasell.reviews.api.index', ['page' => ['size' => 1000]]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('shows a review with its product and customer as relationships', function () {
    // So the product's id differs from the review's and the customer's.
    createProduct('Desk chair');
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $review = apiReview(['customer_id' => $customer->id, 'name' => 'Ada Lovelace']);

    $response = jsonApi('GET', route('larasell.reviews.api.show', $review))->assertOk();

    expect($response->json('data.attributes'))->not->toHaveKeys(['product_id', 'customer_id'])
        ->and($response->json('included'))->toBeNull()
        ->and(snapshotOf($response))->toMatchSnapshot();
});

it('includes the product and customer when asked', function () {
    // So the product's id differs from the review's and the customer's.
    createProduct('Desk chair');
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $review = apiReview(['customer_id' => $customer->id, 'name' => 'Ada Lovelace']);

    $response = jsonApi('GET', route('larasell.reviews.api.show', [$review, 'include' => 'product,customer']))->assertOk();

    expect(snapshotOf($response))->toMatchSnapshot();
});

it('includes each product once in a list', function () {
    $product = createProduct();
    apiReview(['product_id' => $product->id, 'name' => 'Alex']);
    apiReview(['product_id' => $product->id, 'name' => 'Sam']);

    jsonApi('GET', route('larasell.reviews.api.index', ['include' => 'product']))
        ->assertOk()
        ->assertJsonPath('data.0.relationships.product.data.id', (string) $product->id)
        ->assertJsonPath('data.1.relationships.product.data.id', (string) $product->id)
        ->assertJsonCount(1, 'included')
        ->assertJsonPath('included.0.attributes.name', 'Desk lamp');
});

it('answers 404 for a review that does not exist with a JSON:API error', function () {
    $response = jsonApi('GET', route('larasell.reviews.api.show', 999))
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    expect(snapshotOf($response))->toMatchSnapshot();
});

it('links single-review documents to themselves at the top level', function () {
    $review = apiReview();
    $self = route('larasell.reviews.api.show', $review);

    jsonApi('GET', $self)->assertJsonPath('links.self', $self);
});

it('says which JSON:API version every document follows', function (Closure $request) {
    $request()->assertJsonPath('jsonapi', ['version' => '1.1']);

    // Set only while the API answers, so other JSON:API resources in the app keep their own.
    expect(JsonApiResource::$jsonApiInformation)->toBe([]);
})->with([
    'list' => [fn () => jsonApi('GET', route('larasell.reviews.api.index'))],
    'review' => [fn () => jsonApi('GET', route('larasell.reviews.api.show', apiReview()))],
    'error' => [fn () => jsonApi('GET', route('larasell.reviews.api.show', 999))->assertNotFound()],
]);

it('answers 406 when every accepted JSON:API media type has parameters it can\'t serve', function (string $accept) {
    $response = jsonApi('GET', route('larasell.reviews.api.index'), accept: $accept)
        ->assertNotAcceptable()
        ->assertJsonPath('errors.0.source.header', 'Accept');

    expect(snapshotOf($response))->toMatchSnapshot();
})->with([
    'a media type parameter' => ['application/vnd.api+json; charset=utf-8'],
    'an extension' => ['application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"'],
]);

it('serves clients that accept JSON:API without parameters, with a profile, or anything', function (string $accept) {
    jsonApi('GET', route('larasell.reviews.api.index'), accept: $accept)->assertOk();
})->with([
    'one plain instance among others' => ['application/vnd.api+json; charset=utf-8, application/vnd.api+json'],
    'a profile' => ['application/vnd.api+json; profile="https://example.com/timestamps"'],
    'plain JSON' => ['application/json'],
    'anything' => ['*/*'],
]);

it('serves the routes under /api/product-reviews', function () {
    expect(route('larasell.reviews.api.index', absolute: false))->toBe('/api/product-reviews')
        ->and(route('larasell.reviews.api.show', 7, absolute: false))->toBe('/api/product-reviews/7');
});
