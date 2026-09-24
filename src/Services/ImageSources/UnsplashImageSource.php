<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services\ImageSources;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;

class UnsplashImageSource implements ImageSourceInterface
{
    private string $accessKey;

    private array $categories;

    public function __construct()
    {
        $this->accessKey = config('media-generator.sources.unsplash.access_key', '');
        $this->categories = config('media-generator.sources.unsplash.categories', ['nature', 'architecture', 'technology']);
    }

    public function generateUrl(int $width, int $height, ?int $index = null): string
    {
        $category = $this->categories[array_rand($this->categories)];

        if (empty($this->accessKey)) {
            // Fallback to Unsplash Source without API key (limited)
            return "https://source.unsplash.com/{$width}x{$height}/?{$category}";
        }

        // Use Unsplash API for better control
        return "https://api.unsplash.com/photos/random?client_id={$this->accessKey}&w={$width}&h={$height}&query={$category}";
    }

    public function getName(): string
    {
        return 'unsplash';
    }

    public function getDescription(): string
    {
        return 'Unsplash - High-quality photos from photographers worldwide';
    }
}
