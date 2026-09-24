<?php

namespace AhmadChebbo\LaravelMediaGenerator\Facades;

use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService setImageSource(string $sourceName)
 * @method static array generateMedia(array $options)
 */
class MediaGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MediaGeneratorService::class;
    }
}
