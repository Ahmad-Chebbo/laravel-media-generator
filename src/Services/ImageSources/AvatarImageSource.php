<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services\ImageSources;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;

class AvatarImageSource implements ImageSourceInterface
{
    private array $colors;

    public function generateUrl(int $width, int $height, ?int $index = null): string
    {
        return 'https://avatar.iran.liara.run/public';
    }

    public function getName(): string
    {
        return 'Avatar';
    }

    public function getDescription(): string
    {
        return 'Avatar.iran.liara.run - Simple avatar images';
    }
}
