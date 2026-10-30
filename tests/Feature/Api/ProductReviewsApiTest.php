<?php

declare(strict_types=1);

use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Larasell\Larasell\Models\Customer;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewSubmitted;
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

/**
 * A document that creates a valid review.
 *
 * @return array{data: array<string, mixed>}
 */
function newReviewDocument(): array
{
    return reviewDocument(['rating' => 5, 'name' => 'Alex'], ['product' => productLinkage(createProduct()->id)]);
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

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $relationships
 * @return array{data: array<string, mixed>}
 */
function reviewDocument(array $attributes = [], array $relationships = [], array $data = []): array
{
    return ['data' => array_filter([
        'type' => 'product-reviews',
        ...$data,
        'attributes' => $attributes,
        'relationships' => $relationships,
    ], fn (mixed $value): bool => $value !== [])];
}

function productLinkage(int|string $id): array
{
    return ['data' => ['type' => 'products', 'id' => (string) $id]];
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

it('creates a pending review from a JSON:API document', function () {
    Event::fake();
    $product = createProduct();

    $response = jsonApi('POST', route('larasell.reviews.api.store'), reviewDocument(
        ['rating' => 5, 'name' => 'Alex', 'comment' => 'Would buy again.'],
        ['product' => productLinkage($product->id)],
    ))->assertCreated();

    expect(snapshotOf($response))->toMatchSnapshot()
        ->and(ProductReview::sole())
        ->product_id->toBe($product->id)
        ->customer_id->toBeNull()
        ->status->toBe(ProductReviewStatus::Pending);
    Event::assertDispatchedTimes(ProductReviewSubmitted::class, 1);
});

it('creates a review for a customer with their name', function () {
    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    jsonApi('POST', route('larasell.reviews.api.store'), reviewDocument(['rating' => 5], [
        'product' => productLinkage(createProduct()->id),
        'customer' => ['data' => ['type' => 'customers', 'id' => (string) $customer->id]],
    ]))
        ->assertCreated()
        ->assertJsonPath('data.attributes.name', 'Ada Lovelace')
        ->assertJsonPath('data.relationships.customer.data', ['id' => (string) $customer->id, 'type' => 'customers']);
});

it('validates a new review with JSON:API errors that point at the invalid members', function (Closure $document) {
    $response = jsonApi('POST', route('larasell.reviews.api.store'), $document())
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    expect(ProductReview::count())->toBe(0)
        ->and(snapshotOf($response))->toMatchSnapshot();
})->with([
    // Closures, so the product exists by the time the request is made.
    'empty' => [fn () => []],
    'rating out of range and unknown product' => [fn () => reviewDocument(['rating' => 6, 'name' => 'Alex'], ['product' => productLinkage(999)])],
    'no name and no customer' => [fn () => reviewDocument(['rating' => 3], ['product' => productLinkage(createProduct()->id)])],
    'product linked as another type' => [fn () => reviewDocument(['rating' => 3, 'name' => 'Alex'], ['product' => ['data' => ['type' => 'customers', 'id' => (string) createProduct()->id]]])],
]);

it('refuses to create a resource of another type', function () {
    $response = jsonApi('POST', route('larasell.reviews.api.store'), reviewDocument(
        ['rating' => 5, 'name' => 'Alex'],
        ['product' => productLinkage(createProduct()->id)],
        ['type' => 'products'],
    ))->assertConflict();

    expect(ProductReview::count())->toBe(0)
        ->and(snapshotOf($response))->toMatchSnapshot();
});

it('refuses client-generated ids', function () {
    jsonApi('POST', route('larasell.reviews.api.store'), reviewDocument(
        ['rating' => 5, 'name' => 'Alex'],
        ['product' => productLinkage(createProduct()->id)],
        ['id' => '42'],
    ))
        ->assertForbidden()
        ->assertJsonPath('errors.0.source.pointer', '/data/id');

    expect(ProductReview::count())->toBe(0);
});

it('links single-review documents to themselves at the top level', function () {
    $review = apiReview();
    $self = route('larasell.reviews.api.show', $review);

    jsonApi('GET', $self)->assertJsonPath('links.self', $self);
});

it('answers a new review with its location', function () {
    $response = jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())->assertCreated();

    $self = route('larasell.reviews.api.show', ProductReview::sole());

    $response->assertHeader('Location', $self)->assertJsonPath('links.self', $self);
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

it('refuses request documents that are not sent as JSON:API', function (string $contentType) {
    $response = jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument(), contentType: $contentType)
        ->assertUnsupportedMediaType()
        ->assertJsonPath('errors.0.source.header', 'Content-Type');

    expect(ProductReview::count())->toBe(0)
        ->and(snapshotOf($response))->toMatchSnapshot();
})->with([
    'plain JSON' => ['application/json'],
    'a media type parameter' => ['application/vnd.api+json; charset=utf-8'],
    'an extension' => ['application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"'],
]);

it('accepts request documents that apply a profile', function () {
    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument(), contentType: 'application/vnd.api+json; profile="https://example.com/timestamps"')
        ->assertCreated();
});

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

it('limits how many reviews one client can submit per minute', function () {
    config(['larasell.reviews.api.submissions_per_minute' => 2]);

    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())->assertCreated();
    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())->assertCreated();
    $response = jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '60');

    expect(ProductReview::count())->toBe(2)
        ->and(snapshotOf($response))->toMatchSnapshot();

    // Reading isn't limited by submissions.
    jsonApi('GET', route('larasell.reviews.api.index'))->assertOk();

    $this->travel(61)->seconds();
    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())->assertCreated();
});

it('serves the routes under /api/product-reviews', function () {
    expect(route('larasell.reviews.api.index', absolute: false))->toBe('/api/product-reviews')
        ->and(route('larasell.reviews.api.show', 7, absolute: false))->toBe('/api/product-reviews/7');
});
