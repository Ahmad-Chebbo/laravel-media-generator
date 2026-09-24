<?php

namespace AhmadChebbo\LaravelMediaGenerator\Tests\Feature;

use Orchestra\Testbench\TestCase;
use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;
use AhmadChebbo\LaravelMediaGenerator\MediaGeneratorServiceProvider;
use AhmadChebbo\LaravelMediaGenerator\Tests\Models\TestModel;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Illuminate\Support\Facades\Schema;

class MediaGeneratorServiceTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MediaLibraryServiceProvider::class,
            MediaGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function test_it_can_generate_media()
    {
        $this->setUpDatabase();

        $service = app(MediaGeneratorService::class);

        $results = $service
            ->setImageSource('picsum')
            ->generateMedia([
                'count' => 5,
                'model_class' => TestModel::class,
                'collection' => 'test',
            ]);

        $this->assertEquals(5, $results['total']);
        $this->assertGreaterThan(0, $results['success']);
    }

    private function setUpDatabase()
    {
        $this->artisan('migrate:fresh');

        // Create test table
        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
}
