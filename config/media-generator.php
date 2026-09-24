<?php

use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\AvatarImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\DicebearImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PicsumImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PlaceholderImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\UnsplashImageSource;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'concurrent_downloads' => 10,
        'batch_size' => 50,
        'timeout' => 15,
        'image_source' => 'picsum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Dimensions
    |--------------------------------------------------------------------------
    */
    'dimensions' => [
        'min_width' => 400,
        'max_width' => 800,
        'min_height' => 300,
        'max_height' => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Sources
    |--------------------------------------------------------------------------
    */
    'sources' => [
        'picsum' => [
            'class' => PicsumImageSource::class,
            'base_url' => 'https://picsum.photos',
        ],
        'placeholder' => [
            'class' => PlaceholderImageSource::class,
            'base_url' => 'https://placehold.co',
            'colors' => ['FF0000', '00FF00', '0000FF', 'FFFF00', 'FF00FF', '00FFFF', 'FFA500', '800080'],
        ],
        'unsplash' => [
            'class' => UnsplashImageSource::class,
            'base_url' => 'https://source.unsplash.com',
        ],
        'avatar' => [
            'class' => AvatarImageSource::class,
            'base_url' => 'https://avatar.iran.liara.run/public',
        ],
        'dicebear' => [
            'class' => DicebearImageSource::class,
            'base_url' => 'https://api.dicebear.com',
            // Avatar style — any of the 61 official DiceBear styles.
            // See DicebearImageSource::AVAILABLE_STYLES for the full list.
            'style' => 'bottts',
            'api_version' => '10.x',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Define your model mappings and their default field values
    |--------------------------------------------------------------------------
    */
    'models' => [
        // Example configuration - users should override this
        'default' => [
            'class' => null, // Must be set by user
            'collection' => 'default',
            'fields' => [],
            'relationships' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'temp_directory' => 'temp_media_generator',
        'disk' => 'local',
    ],
];
