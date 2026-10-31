<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Larasell\Admin\Audit\SystemActor;
use Larasell\Admin\Models\ApiToken;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Actions\DeleteProductReview;
use Larasell\Reviews\Actions\UpdateProductReview;
use Larasell\Reviews\Enums\ProductReviewStatus;
use Larasell\Reviews\Events\ProductReviewApproved;
use Larasell\Reviews\Http\Controllers\Api\ProductReviewController as ApiProductReviewController;
use Larasell\Reviews\Models\ProductReview;

beforeEach(function () {
    $this->review = ProductReview::create([
        'product_id' => createProduct()->id,
        'name' => 'Alex Parker',
        'rating' => 4,
        'comment' => 'Bright and sturdy.',
    ]);

    $this->token = 'larasell_'.Str::random(40);
    $this->apiToken = ApiToken::query()->create(['name' => 'Storefront', 'token_hash' => hash('sha256', $this->token), 'expires_at' => now()->addMonth()]);
});

/**
 * @param  array<string, mixed>  $document
 */
function auditedApiCall(string $method, string $uri, array $document = []): void
{
    test()->json($method, $uri, $document, [
        'CONTENT_TYPE' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
        'Authorization' => 'Bearer '.test()->token,
    ])->assertSuccessful();
}

/**
 * @return list<object>
 */
function auditRecords(): array
{
    return DB::table('larasell_audit_log')->orderBy('id')->get()->all();
}

/**
 * @return array<string, mixed>
 */
function auditProperties(object $record): array
{
    return json_decode($record->properties, true);
}

it('audits an admin approving or rejecting a review from its row, with the status it had', function (string $action, ProductReviewStatus $from, string $event) {
    $admin = signInAdmin();
    $this->review->update(['status' => $from]);

    $this->post(route("larasell.admin.product-reviews.{$action}.store", $this->review))->assertRedirect();

    $record = collect(auditRecords())->sole();

    expect($record)->toMatchArray([
        'event' => $event,
        'category' => 'business_critical',
        'actor_type' => 'admin',
        'actor_id' => (string) $admin->id,
        'subject_type' => 'product_review',
        'subject_id' => (string) $this->review->id,
        'source' => 'web',
    ])
        ->and(auditProperties($record))->toBe([
            'product_id' => $this->review->product_id,
            'status' => ['from' => $from->value, 'to' => $event === 'product_review.approved' ? 'approved' : 'rejected'],
        ]);
})->with([
    'approve a pending review' => ['approval', ProductReviewStatus::Pending, 'product_review.approved'],
    'approve a rejected review' => ['approval', ProductReviewStatus::Rejected, 'product_review.approved'],
    'reject an approved review' => ['rejection', ProductReviewStatus::Approved, 'product_review.rejected'],
]);

it('audits approving and rejecting through the API as the API token', function () {
    auditedApiCall('PATCH', route('larasell.reviews.api.update', $this->review), ['data' => ['type' => 'product-reviews', 'id' => (string) $this->review->id, 'attributes' => ['status' => 'rejected']]]);

    expect(collect(auditRecords())->sole())->toMatchArray([
        'event' => 'product_review.rejected',
        'actor_type' => 'api_token',
        'actor_id' => (string) $this->apiToken->id,
        'source' => 'api',
    ]);
});

it('does not audit a review that already has the status', function () {
    app(ApproveProductReview::class)->handle($this->review, moderator());
    app(ApproveProductReview::class)->handle($this->review, moderator());

    expect(collect(auditRecords())->pluck('event')->all())->toBe(['product_review.approved']);
});

it('audits which fields an update changed and the rating before and after, without the reviewer\'s text', function () {
    auditedApiCall('PATCH', route('larasell.reviews.api.update', $this->review), ['data' => [
        'type' => 'product-reviews',
        'id' => (string) $this->review->id,
        'attributes' => ['rating' => 2, 'name' => 'A. Parker', 'comment' => 'Wobbles after a week.'],
    ]]);

    $record = collect(auditRecords())->sole();

    expect($record)->toMatchArray([
        'event' => 'product_review.updated',
        'actor_type' => 'api_token',
        'subject_id' => (string) $this->review->id,
    ])
        ->and(auditProperties($record))->toBe([
            'product_id' => $this->review->product_id,
            'changed' => ['rating', 'name', 'comment'],
            'rating' => ['from' => 4, 'to' => 2],
        ])
        ->and($record->properties)->not->toContain('Parker')->not->toContain('sturdy')->not->toContain('Wobbles');
});

it('leaves the rating out of an update that kept it', function () {
    app(UpdateProductReview::class)->handle($this->review, ['rating' => 4, 'comment' => null], moderator());

    expect(auditProperties(collect(auditRecords())->sole()))->toBe([
        'product_id' => $this->review->product_id,
        'changed' => ['comment'],
    ]);
});

it('does not audit an update that changed nothing', function () {
    app(UpdateProductReview::class)->handle($this->review, ['rating' => 4, 'name' => 'Alex Parker'], moderator());

    expect(auditRecords())->toBe([]);
});

it('audits deleting a review with its rating and status, without the reviewer\'s text', function () {
    $this->review->update(['status' => ProductReviewStatus::Approved]);

    auditedApiCall('DELETE', route('larasell.reviews.api.destroy', $this->review));

    $record = collect(auditRecords())->sole();

    expect($record)->toMatchArray([
        'event' => 'product_review.deleted',
        'actor_type' => 'api_token',
        'subject_type' => 'product_review',
        'subject_id' => (string) $this->review->id,
    ])
        ->and(auditProperties($record))->toBe([
            'product_id' => $this->review->product_id,
            'rating' => 4,
            'status' => 'approved',
        ]);
});

it('does not audit a customer submitting a review', function () {
    auditedApiCall('POST', route('larasell.reviews.api.store'), ['data' => [
        'type' => 'product-reviews',
        'attributes' => ['rating' => 5, 'name' => 'Sam'],
        'relationships' => ['product' => ['data' => ['type' => 'products', 'id' => (string) $this->review->product_id]]],
    ]]);

    expect(ProductReview::count())->toBe(2)
        ->and(auditRecords())->toBe([]);
});

it('records the actor a job or command passes', function () {
    app(DeleteProductReview::class)->handle($this->review, new SystemActor('spam-filter'));

    expect(collect(auditRecords())->sole())->toMatchArray([
        'actor_type' => 'system',
        'actor_id' => 'spam-filter',
    ]);
});

it('records an unknown actor, and why, when the API is configured to authenticate no one', function () {
    // As larasell.reviews.api.middleware => ['api'] registers it.
    Route::middleware('api')->delete('open-api/product-reviews/{review}', [ApiProductReviewController::class, 'destroy']);

    $this->deleteJson('open-api/product-reviews/'.$this->review->id)->assertNoContent();

    expect(collect(auditRecords())->sole())->toMatchArray([
        'actor_type' => 'unknown',
        'actor_id' => null,
        'unknown_actor_reason' => 'The product reviews API authenticates no one, as configured in larasell.reviews.api.middleware.',
    ]);
});

it('changes nothing and announces nothing when the change can\'t be audited', function (Closure $change) {
    Event::fake([ProductReviewApproved::class]);
    Schema::drop('larasell_audit_log');

    expect(fn () => $change($this->review))->toThrow(Exception::class);

    expect(ProductReview::find($this->review->id))
        ->not->toBeNull()
        ->status->toBe(ProductReviewStatus::Pending)
        ->rating->toBe(4);
    Event::assertNotDispatched(ProductReviewApproved::class);
})->with([
    'approve' => [fn (ProductReview $review) => app(ApproveProductReview::class)->handle($review, moderator())],
    'update' => [fn (ProductReview $review) => app(UpdateProductReview::class)->handle($review, ['rating' => 1], moderator())],
    'delete' => [fn (ProductReview $review) => app(DeleteProductReview::class)->handle($review, moderator())],
]);
