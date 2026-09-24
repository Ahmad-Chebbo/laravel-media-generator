<?php

declare(strict_types=1);

namespace AhmadChebbo\LaravelMediaGenerator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use AhmadChebbo\LaravelMediaGenerator\MediaGeneratorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MediaGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configure the database
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure media library
        $app['config']->set('media-library.disk_name', 'public');
        $app['config']->set('media-library.max_file_size', 1024 * 1024 * 10); // 10MB
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations if needed
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
