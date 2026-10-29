<?php

declare(strict_types=1);

namespace Larasell\Reviews\Tests;

use Illuminate\Foundation\Application;
use Larasell\Reviews\ReviewsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ReviewsServiceProvider::class,
        ];
    }
}
