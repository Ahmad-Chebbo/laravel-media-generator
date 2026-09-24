<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services\ImageSources;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;

class DicebearImageSource implements ImageSourceInterface
{
    public const DEFAULT_STYLE = 'bottts';

    public const AVAILABLE_STYLES = [
        'adventurer',
        'adventurer-neutral',
        'avataaars',
        'avataaars-neutral',
        'big-ears',
        'big-ears-neutral',
        'big-smile',
        'blobs',
        'bottts',
        'bottts-neutral',
        'cameo',
        'clay',
        'constellation',
        'critters',
        'croodles',
        'croodles-neutral',
        'cutouts',
        'disco',
        'dylan',
        'fun-emoji',
        'gaze',
        'glass',
        'glyphs',
        'icons',
        'identicon',
        'initial-face',
        'initials',
        'landscape',
        'line-face',
        'loops',
        'lorelei',
        'lorelei-neutral',
        'marbles',
        'micah',
        'miniavs',
        'moods',
        'notionists',
        'notionists-neutral',
        'open-peeps',
        'patchwork',
        'personas',
        'pixel-art',
        'pixel-art-neutral',
        'pixelbot',
        'planets',
        'rings',
        'shadows',
        'shape-grid',
        'shapes',
        'slice',
        'sprouts',
        'squircles',
        'stack',
        'stripes',
        'thumbs',
        'toon-head',
        'triangles',
        'voxel-art',
        'voxel-bot',
        'waves',
        'weave',
    ];

    private string $style;

    private string $apiVersion;

    public function __construct()
    {
        $configuredStyle = config('media-generator.sources.dicebear.style', self::DEFAULT_STYLE);

        $this->style = in_array($configuredStyle, self::AVAILABLE_STYLES, true)
            ? $configuredStyle
            : self::DEFAULT_STYLE;

        $this->apiVersion = config('media-generator.sources.dicebear.api_version', '10.x');
    }

    /**
     * Set the avatar style to use.
     *
     * @param  string  $style  A valid DiceBear style name (e.g. 'bottts', 'pixel-art').
     */
    public function setStyle(string $style): static
    {
        $this->style = in_array($style, self::AVAILABLE_STYLES, true)
            ? $style
            : self::DEFAULT_STYLE;

        return $this;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * Generate a DiceBear avatar URL.
     *
     * Width and height are ignored for the SVG-based HTTP API (DiceBear
     * avatars are inherently vector). The index is used as a deterministic
     * seed so every run produces the same avatar for a given position.
     */
    public function generateUrl(int $width, int $height, ?int $index = null): string
    {
        $seed = $index ?? rand(1, 999999);
        $size = min(256, max(64, $width));

        return "https://api.dicebear.com/{$this->apiVersion}/{$this->style}/jpg?seed={$seed}&size={$size}";
    }

    public function getName(): string
    {
        return 'dicebear';
    }

    public function getDescription(): string
    {
        return 'DiceBear - Unique deterministic SVG/raster avatars (style: '.$this->style.')';
    }
}
