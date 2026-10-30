<?php

declare(strict_types=1);

use Larasell\Reviews\ReviewsServiceProvider;

it('boots inside the admin panel', function () {
    expect(app()->getProviders(ReviewsServiceProvider::class))->toHaveCount(1)
        ->and(config('larasell.reviews'))->toBeArray();

    signInAdmin();

    $this->get(route('larasell.admin.home'))->assertOk();
});
