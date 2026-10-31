<?php

declare(strict_types=1);

namespace Larasell\Reviews\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Larasell\Admin\AdminServiceProvider;
use Larasell\Larasell\LarasellServiceProvider;
use Larasell\Reviews\ReviewsServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Tests don't read the playground's .env, so they run the same on every machine.
     */
    protected $loadEnvironmentVariables = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Apps may switch to immutable dates, so the package can't rely on Illuminate\Support\Carbon.
        Date::use(CarbonImmutable::class);

        $this->withoutVite();
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            HeadServiceProvider::class,
            AdminServiceProvider::class,
            InertiaServiceProvider::class,
            LarasellServiceProvider::class,
            LaravelDataServiceProvider::class,
            ReviewsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        config([
            'cache.default' => 'array',
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                // Like production databases, so deletes cascade and null out as the migrations define.
                'foreign_key_constraints' => true,
            ],
        ]);
    }
}
