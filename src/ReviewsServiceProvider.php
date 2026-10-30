<?php

namespace Larasell\Reviews;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Reviews\Http\Controllers\ProductReviewApprovalController;
use Larasell\Reviews\Http\Controllers\ProductReviewController;
use Larasell\Reviews\Http\Controllers\ProductReviewRejectionController;
use Larasell\Reviews\Tables\ProductReviews;

class ReviewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larasell/reviews.php', 'larasell.reviews');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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
}
