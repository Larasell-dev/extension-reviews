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
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('larasell.models.product', Product::class);
    }
}
