<?php

namespace AhmadChebbo\LaravelMediaGenerator;

use AhmadChebbo\LaravelMediaGenerator\Commands\GenerateMediaCommand;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PicsumImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PlaceholderImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;
use Illuminate\Support\ServiceProvider;

class MediaGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MediaGeneratorService::class);

        // Register image sources
        $this->app->bind('media-generator.sources.picsum', PicsumImageSource::class);
        $this->app->bind('media-generator.sources.placeholder', PlaceholderImageSource::class);

        $this->mergeConfigFrom(__DIR__.'/../config/media-generator.php', 'media-generator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateMediaCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/media-generator.php' => config_path('media-generator.php'),
            ], 'media-generator-config');
        }
    }
}
