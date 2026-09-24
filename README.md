# Laravel Media Generator

A flexible Laravel package for generating and managing media files with Spatie Media Library integration. Perfect for testing, seeding, and development environments.

## Features

- 🚀 **Concurrent Downloads** - Download multiple images simultaneously for faster generation
- 🎨 **Multiple Image Sources** - Support for Picsum, Placeholder.com, and custom sources
- 🔧 **Highly Configurable** - Customize dimensions, batch sizes, and model fields
- 📦 **Spatie Media Library Integration** - Seamless integration with the popular media library
- 🎯 **Model Agnostic** - Works with any Eloquent model that uses Spatie Media Library
- ⚡ **Batch Processing** - Efficient batch processing with progress tracking

## Installation

```bash
composer require ahmad-chebbo/laravel-media-generator
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=media-generator-config
```

## Quick Start

### Basic Usage

```bash
# Generate 50 images for User model
php artisan media:generate --model="App\Models\User" --count=50

# Use different image source
php artisan media:generate --model="App\Models\Post" --count=20 --source=placeholder

# Specify media collection
php artisan media:generate --model="App\Models\Product" --count=100 --collection=gallery
```

### Generate media for existing record

```bash
php artisan media:generate --model="App\Models\Product" --count=100 --record-id=1
```

### Generate media for new records

```bash
php artisan media:generate --model="App\Models\Product" --count=100
```

### Programmatic Usage

```php
use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;

$service = app(MediaGeneratorService::class);

$results = $service
    ->setImageSource('picsum')
    ->generateMedia([
        'count' => 50,
        'model_class' => \App\Models\User::class,
        'collection' => 'avatars',
        'model_fields' => [
            'name' => fn($index) => "User {$index}",
            'email' => fn($index) => "user{$index}@example.com",
        ],
    ]);
```

## Configuration

The package comes with a comprehensive configuration file:

```php
// config/media-generator.php
return [
    'defaults' => [
        'concurrent_downloads' => 10,
        'batch_size' => 50,
        'timeout' => 15,
        'image_source' => 'picsum',
    ],

    'dimensions' => [
        'min_width' => 400,
        'max_width' => 800,
        'min_height' => 300,
        'max_height' => 600,
    ],

    'sources' => [
        'picsum' => [
            'class' => \AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PicsumImageSource::class,
        ],
        'placeholder' => [
            'class' => \AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PlaceholderImageSource::class,
            'colors' => ['FF0000', '00FF00', '0000FF'],
        ],
    ],
];
```

## Advanced Usage

### Custom Image Sources

Create your own image source by implementing the `ImageSourceInterface`:

```php
use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;

class CustomImageSource implements ImageSourceInterface
{
    public function generateUrl(int $width, int $height, int $index = null): string
    {
        return "https://example.com/image/{$width}x{$height}/{$index}";
    }

    public function getName(): string
    {
        return 'custom';
    }

    public function getDescription(): string
    {
        return 'My custom image source';
    }
}
```

Register it in your service provider:

```php
$this->app->bind('media-generator.sources.custom', CustomImageSource::class);
```

### Dynamic Model Fields

Use closures for dynamic field values:

```php
$service->generateMedia([
    'count' => 100,
    'model_class' => \App\Models\Product::class,
    'model_fields' => [
        'name' => fn($index) => "Product {$index}",
        'price' => fn() => rand(1000, 50000) / 100,
        'category_id' => fn() => rand(1, 10),
        'created_at' => fn() => now()->subDays(rand(1, 365)),
    ],
]);
```

### Progress Tracking

Add custom progress tracking:

```php
$service->generateMedia([
    'count' => 1000,
    'model_class' => \App\Models\Image::class,
    'progress_callback' => function ($current, $total) {
        $percentage = round(($current / $total) * 100, 2);
        echo "Progress: {$current}/{$total} ({$percentage}%)\n";
    },
]);
```

## Command Options

| Option         | Description                        | Default     |
| -------------- | ---------------------------------- | ----------- |
| `--model`      | Fully qualified model class name   | Required    |
| `--count`      | Number of images to generate       | Interactive |
| `--source`     | Image source (picsum, placeholder) | picsum      |
| `--dicebear-style` | DiceBear style to use | bottts |
| `--collection` | Media collection name              | default     |
| `--concurrent` | Number of concurrent downloads     | 10          |
| `--batch-size` | Batch size for processing          | 50          |

## Requirements

- PHP 8.1+
- Laravel 9.0+
- Spatie Laravel Media Library 10.0+

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
