<?php

namespace AhmadChebbo\LaravelMediaGenerator\Contracts;

interface ImageSourceInterface
{
    public function generateUrl(int $width, int $height, ?int $index = null): string;

    public function getName(): string;

    public function getDescription(): string;
}
