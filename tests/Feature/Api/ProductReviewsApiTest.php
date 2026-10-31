<?php

declare(strict_types=1);

use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Larasell\Admin\Actions\ApiTokens\CreateApiToken;
use Larasell\Larasell\Models\Customer;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewApproved;
use Larasell\Reviews\Events\ProductReviewDeleted;
use Larasell\Reviews\Events\ProductReviewRejected;
use Larasell\Reviews\Events\ProductReviewSubmitted;
use Larasell\Reviews\Events\ProductReviewUpdated;
use Larasell\Reviews\Models\ProductReview;

beforeEach(function () {
    // Fixed, so the timestamps in the snapshots don't change from run to run.
    $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 30));

    $this->apiToken = apiToken();
});

/**
 * A plaintext API token, as an admin creates one under Settings > API tokens.
 */
function apiToken(): string
{
    return app(CreateApiToken::class)->handle(['name' => 'Storefront', 'expires_in' => '1_month'], null)[1];
}

/**
 * The response body as readable JSON, so snapshot diffs show what changed.
 */
function snapshotOf(TestResponse $response): string
{
    return json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

/**
 * Sends a request the way a JSON:API client does, with the JSON:API media type and the test's
 * API token, or the given one.
 *
 * @param  array<string, mixed>  $document
 */
function jsonApi(string $method, string $uri, array $document = [], string $contentType = 'application/vnd.api+json', string $accept = 'application/vnd.api+json', ?string $token = null): TestResponse
{
    return test()->json($method, $uri, $document, [
        'CONTENT_TYPE' => $contentType,
        'Accept' => $accept,
        'Authorization' => 'Bearer '.($token ?? test()->apiToken),
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

it('answers 400 to an include path it can\'t serve, before changing anything', function (string $method, Closure $uri, Closure $document) {
    $review = apiReview();

    $response = jsonApi($method, $uri($review), $document($review))
        ->assertBadRequest()
        ->assertJsonPath('errors.0.source.parameter', 'include');

    expect(ProductReview::count())->toBe(1)
        ->and($review->fresh()->rating)->toBe(4)
        ->and(snapshotOf($response))->toMatchSnapshot();
})->with([
    'an unknown relationship' => ['GET', fn (ProductReview $review) => route('larasell.reviews.api.show', [$review, 'include' => 'author']), fn () => []],
    'a nested path' => ['GET', fn () => route('larasell.reviews.api.index', ['include' => 'product.categories']), fn () => []],
    'one bad path among good ones' => ['GET', fn () => route('larasell.reviews.api.index', ['include' => 'product,author']), fn () => []],
    'on create' => ['POST', fn () => route('larasell.reviews.api.store', ['include' => 'author']), fn () => newReviewDocument()],
    'on update' => ['PATCH', fn (ProductReview $review) => route('larasell.reviews.api.update', [$review, 'include' => 'author']), fn (ProductReview $review) => reviewDocument(['rating' => 1], data: ['id' => (string) $review->id])],
]);

it('includes nothing for an empty include', function () {
    jsonApi('GET', route('larasell.reviews.api.show', [apiReview(), 'include' => '']))
        ->assertOk()
        ->assertJsonMissingPath('included');
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
    'no data' => [fn () => ['meta' => ['client' => 'storefront']]],
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

it('updates a review\'s rating, name and comment', function () {
    $review = apiReview();
    Event::fake();

    $response = jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(
        ['rating' => 2, 'name' => 'Alex P.', 'comment' => null],
        data: ['id' => (string) $review->id],
    ))->assertOk();

    expect(snapshotOf($response))->toMatchSnapshot()
        ->and($review->fresh())
        ->rating->toBe(2)
        ->name->toBe('Alex P.')
        ->comment->toBeNull();
    Event::assertDispatchedTimes(ProductReviewUpdated::class, 1);
});

it('leaves attributes out of an update unchanged', function () {
    $review = apiReview();
    Event::fake();

    jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(['rating' => 4], data: ['id' => (string) $review->id]))->assertOk();

    expect($review->fresh())
        ->name->toBe('Alex')
        ->comment->toBe('Bright and sturdy.');
    Event::assertNotDispatched(ProductReviewUpdated::class);
});

it('approves and rejects a review through an update', function (string $status, string $event, string $other) {
    $review = apiReview();
    Event::fake();

    jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(['status' => $status], data: ['id' => (string) $review->id]))
        ->assertOk()
        ->assertJsonPath('data.attributes.status', $status);

    expect($review->fresh()->status->value)->toBe($status);
    Event::assertDispatchedTimes($event, 1);
    Event::assertNotDispatched($other);
    Event::assertNotDispatched(ProductReviewUpdated::class);
})->with([
    'approve' => ['approved', ProductReviewApproved::class, ProductReviewRejected::class],
    'reject' => ['rejected', ProductReviewRejected::class, ProductReviewApproved::class],
]);

it('validates an update', function () {
    $review = apiReview();

    $response = jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(
        ['rating' => 0, 'name' => '', 'status' => 'pending'],
        data: ['id' => (string) $review->id],
    ))->assertUnprocessable();

    expect(snapshotOf($response))->toMatchSnapshot()
        ->and($review->fresh()->rating)->toBe(4);
});

it('refuses an update whose type or id does not match the url', function (Closure $data) {
    $review = apiReview();
    $other = apiReview();

    $response = jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(['rating' => 1], data: $data($review, $other)))
        ->assertConflict();

    expect($review->fresh()->rating)->toBe(4)
        ->and(snapshotOf($response))->toMatchSnapshot();
})->with([
    'another review\'s id' => [fn (ProductReview $review, ProductReview $other) => ['id' => (string) $other->id]],
    'another type' => [fn (ProductReview $review) => ['type' => 'products', 'id' => (string) $review->id]],
]);

it('refuses to change a review\'s product or customer', function () {
    $review = apiReview();

    jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(
        relationships: ['product' => productLinkage(createProduct('Desk chair')->id)],
        data: ['id' => (string) $review->id],
    ))
        ->assertForbidden()
        ->assertJsonPath('errors.0.source.pointer', '/data/relationships');

    expect($review->fresh()->product_id)->toBe($review->product_id);
});

it('deletes a review', function () {
    $review = apiReview();
    Event::fake();

    jsonApi('DELETE', route('larasell.reviews.api.destroy', $review))->assertNoContent();

    expect(ProductReview::count())->toBe(0);
    Event::assertDispatched(ProductReviewDeleted::class, fn (ProductReviewDeleted $event) => $event->review->is($review));
});

it('links single-review documents to themselves at the top level', function () {
    $review = apiReview();
    $self = route('larasell.reviews.api.show', $review);

    jsonApi('GET', $self)->assertJsonPath('links.self', $self);
    jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(['rating' => 1], data: ['id' => (string) $review->id]))
        ->assertJsonPath('links.self', $self);
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

it('refuses an update that is not sent as JSON:API', function () {
    $review = apiReview();

    jsonApi('PATCH', route('larasell.reviews.api.update', $review), reviewDocument(['rating' => 1], data: ['id' => (string) $review->id]), contentType: 'application/json')
        ->assertUnsupportedMediaType();

    expect($review->fresh()->rating)->toBe(4);
});

it('answers 400 to a request document that is not a JSON object', function (string $method, Closure $uri, string $body) {
    $review = apiReview();

    $response = test()->call($method, $uri($review), server: [
        'CONTENT_TYPE' => 'application/vnd.api+json',
        'HTTP_ACCEPT' => 'application/vnd.api+json',
        'HTTP_AUTHORIZATION' => 'Bearer '.test()->apiToken,
    ], content: $body)
        ->assertBadRequest()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    expect(ProductReview::count())->toBe(1)
        ->and($review->fresh()->rating)->toBe(4)
        ->and(snapshotOf($response))->toMatchSnapshot();
})->with([
    'malformed JSON on create' => ['POST', fn () => route('larasell.reviews.api.store'), '{"data":'],
    'malformed JSON on update' => ['PATCH', fn (ProductReview $review) => route('larasell.reviews.api.update', $review), '{"data":'],
    'an array' => ['POST', fn () => route('larasell.reviews.api.store'), '[]'],
    'no body' => ['POST', fn () => route('larasell.reviews.api.store'), ''],
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

it('updates with PATCH only', function () {
    $review = apiReview();

    jsonApi('POST', route('larasell.reviews.api.update', $review), reviewDocument(['rating' => 1], data: ['id' => (string) $review->id]))
        ->assertMethodNotAllowed()
        ->assertJsonPath('errors.0.status', '405');

    expect($review->fresh()->rating)->toBe(4);
});

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

it('limits submissions per API token', function () {
    config(['larasell.reviews.api.submissions_per_minute' => 1]);

    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())->assertCreated();
    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument())->assertTooManyRequests();

    // Another client, from the same address.
    jsonApi('POST', route('larasell.reviews.api.store'), newReviewDocument(), token: apiToken())->assertCreated();
});

it('requires an API token from the admin panel', function (string $method, Closure $uri, ?string $token) {
    $review = apiReview();
    $headers = ['CONTENT_TYPE' => 'application/vnd.api+json', 'Accept' => 'application/vnd.api+json'];

    $response = $this->json($method, $uri($review), newReviewDocument(), $token === null ? $headers : [...$headers, 'Authorization' => "Bearer {$token}"])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    expect(snapshotOf($response))->toMatchSnapshot()
        ->and(ProductReview::count())->toBe(1)
        ->and($review->fresh()->rating)->toBe(4);
})->with([
    'list' => ['GET', fn () => route('larasell.reviews.api.index'), null],
    // Not a 404, which would tell a client without a token which reviews exist.
    'show a review that does not exist' => ['GET', fn () => route('larasell.reviews.api.show', 999), null],
    'create' => ['POST', fn () => route('larasell.reviews.api.store'), null],
    'update' => ['PATCH', fn (ProductReview $review) => route('larasell.reviews.api.update', $review), null],
    'delete' => ['DELETE', fn (ProductReview $review) => route('larasell.reviews.api.destroy', $review), null],
    'unknown token' => ['GET', fn () => route('larasell.reviews.api.index'), 'larasell_unknown'],
]);

it('serves the routes under /api/product-reviews', function () {
    expect(route('larasell.reviews.api.index', absolute: false))->toBe('/api/product-reviews')
        ->and(route('larasell.reviews.api.show', 7, absolute: false))->toBe('/api/product-reviews/7');
});
