<?php

namespace Larasell\Reviews\Tests;

use Larasell\Larasell\LarasellServiceProvider;
use Larasell\Reviews\ReviewsServiceProvider;
use Larasell\Reviews\Tests\Fixtures\Product;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LarasellServiceProvider::class,
            ReviewsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $connection = env('DB_CONNECTION', 'sqlite');

        $app['config']->set('database.default', $connection);
        $app['config']->set('database.connections.sqlite.database', env('DB_DATABASE', ':memory:'));
        $app['config']->set('larasell.models.product', Product::class);
    }
}
