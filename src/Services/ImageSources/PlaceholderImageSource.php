<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services\ImageSources;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;

class PlaceholderImageSource implements ImageSourceInterface
{
    private array $colors;

    public function __construct()
    {
        $this->colors = config('media-generator.sources.placeholder.colors', [
            'FF0000', '00FF00', '0000FF', 'FFFF00', 'FF00FF', '00FFFF', 'FFA500', '800080',
        ]);
    }

    public function generateUrl(int $width, int $height, ?int $index = null): string
    {
        $color = $this->colors[array_rand($this->colors)];

        return "https://placehold.co/{$width}x{$height}/{$color}/FFFFFF.jpg";
    }

    public function getName(): string
    {
        return 'placeholder';
    }

    public function getDescription(): string
    {
        return 'Placeholder.com - Simple colored placeholder images';
    }
}
