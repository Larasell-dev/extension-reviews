<?php

namespace Larasell\Reviews;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Reviews\Http\Controllers\Api\ProductReviewController as ApiProductReviewController;
use Larasell\Reviews\Http\Controllers\ProductReviewApprovalController;
use Larasell\Reviews\Http\Controllers\ProductReviewController;
use Larasell\Reviews\Http\Controllers\ProductReviewRejectionController;
use Larasell\Reviews\Http\Exceptions\JsonApiError;
use Larasell\Reviews\Http\Middleware\JsonApi;
use Larasell\Reviews\Tables\ProductReviews;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ReviewsServiceProvider extends ServiceProvider
{
    public const string API_PREFIX = 'api/product-reviews';

    private const string SUBMISSION_LIMITER = 'larasell-reviews-submissions';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larasell/reviews.php', 'larasell.reviews');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Per client: the signed-in user once the API has authentication, its IP address until then.
        RateLimiter::for(self::SUBMISSION_LIMITER, fn (Request $request): Limit => Limit::perMinute((int) config('larasell.reviews.api.submissions_per_minute'))
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        Route::middleware([...config('larasell.reviews.api.middleware'), JsonApi::class])
            ->prefix(self::API_PREFIX)
            ->name('larasell.reviews.api.')
            ->group(function (): void {
                Route::get('/', [ApiProductReviewController::class, 'index'])->name('index');
                Route::post('/', [ApiProductReviewController::class, 'store'])->middleware('throttle:'.self::SUBMISSION_LIMITER)->name('store');
                Route::get('{review}', [ApiProductReviewController::class, 'show'])->name('show');
                Route::patch('{review}', [ApiProductReviewController::class, 'update'])->name('update');
            });

        $this->renderApiErrors();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/larasell/reviews.php' => config_path('larasell/reviews.php'),
            ], 'larasell-reviews-config');
        }

        // The review page and its styles, built by vite.config.ts under the same name.
        AdminPanel::resolve()->vite('larasell-reviews', dirname(__DIR__).'/public', [
            'resources/js/admin.ts',
            'resources/css/admin.css',
        ]);

        Navigation::index('Reviews', 'product-reviews', ProductReviews::class, icon: Icon::Star)->under(NavItem::Products);

        AdminPanel::resolve()->routes(function (): void {
            Route::get('product-reviews/{productReview}', [ProductReviewController::class, 'show'])->name('product-reviews.show');
            Route::post('product-reviews/{productReview}/approval', [ProductReviewApprovalController::class, 'store'])->name('product-reviews.approval.store');
            Route::post('product-reviews/{productReview}/rejection', [ProductReviewRejectionController::class, 'store'])->name('product-reviews.rejection.store');
        });
    }

    /**
     * Errors on the API's routes are JSON:API error documents: one error object per invalid
     * member, each pointing at it, and one for other client errors such as a missing review.
     * Other requests keep the application's error responses.
     */
    private function renderApiErrors(): void
    {
        $this->app->make(ExceptionHandler::class)->renderable(function (Throwable $e, Request $request): ?JsonResponse {
            // By path rather than route name: a 405 is raised before any route matches.
            if (! $request->is(self::API_PREFIX, self::API_PREFIX.'/*')) {
                return null;
            }

            $error = fn (int $status, ?string $detail = null, array $source = []): array => array_filter([
                'status' => (string) $status,
                'title' => Response::$statusTexts[$status] ?? 'Error',
                'detail' => $detail,
                'source' => $source,
            ]);

            [$status, $errors] = match (true) {
                $e instanceof ValidationException => [422, collect($e->errors())
                    ->flatMap(fn (array $messages, string $key) => array_map(
                        fn (string $message) => $error(422, $message, ['pointer' => '/'.str_replace('.', '/', $key)]),
                        $messages,
                    ))
                    ->values()
                    ->all()],
                $e instanceof JsonApiError => [$e->getStatusCode(), [$error($e->getStatusCode(), $e->getMessage(), $e->source)]],
                // Route model binding's message names the model class; say it in the API's terms.
                $e instanceof NotFoundHttpException && $e->getPrevious() instanceof ModelNotFoundException => [404, [$error(404, 'No product review has this id.')]],
                $e instanceof AuthenticationException => [401, [$error(401)]],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), [$error($e->getStatusCode(), $e->getMessage() ?: null)]],
                default => [null, null],
            };

            if ($status === null) {
                return null;
            }

            return new JsonResponse(
                ['jsonapi' => ['version' => JsonApi::VERSION], 'errors' => $errors],
                $status,
                // Keeps headers such as the rate limit's Retry-After.
                [...$e instanceof HttpExceptionInterface ? $e->getHeaders() : [], 'Content-Type' => JsonApi::MEDIA_TYPE],
            );
        });
    }
}
