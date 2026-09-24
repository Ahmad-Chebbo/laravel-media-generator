<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services\ImageSources;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;

class PicsumImageSource implements ImageSourceInterface
{
    public function generateUrl(int $width, int $height, ?int $index = null): string
    {
        $seed = $index ? $index : rand(1, 10000);

        return "https://picsum.photos/seed/{$seed}/{$width}/{$height}.jpg";
    }

    public function getName(): string
    {
        return 'picsum';
    }

    public function getDescription(): string
    {
        return 'Lorem Picsum - Beautiful random images';
    }
}
