<?php

namespace Larasell\Reviews;

use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
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

        // The admin pages and their styles, built by vite.config.ts under the same name.
        AdminPanel::resolve()->vite('larasell-reviews', dirname(__DIR__).'/public', [
            'resources/js/admin.ts',
            'resources/css/admin.css',
        ]);

        Navigation::index('Reviews', 'product-reviews', ProductReviews::class, icon: Icon::Star)->under(NavItem::Products);
    }
}
