<?php

declare(strict_types=1);

use Larasell\Reviews\ReviewsServiceProvider;

it('registers the service provider', function () {
    expect(app()->getProviders(ReviewsServiceProvider::class))->toHaveCount(1);
});
