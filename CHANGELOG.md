# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-09-24

### Added
- Initial release of Laravel Media Generator package (`ahmad-chebbo/laravel-media-generator`).
- Seamless integration with Spatie Media Library (`spatie/laravel-medialibrary`).
- Multiple built-in image sources:
  - **Picsum** (`PicsumImageSource`) via `picsum.photos`
  - **Placeholder** (`PlaceholderImageSource`) via `placehold.co`
  - **Unsplash** (`UnsplashImageSource`)
  - **Avatar** (`AvatarImageSource`) via `avatar.iran.liara.run`
- Extensible `ImageSourceInterface` allowing custom image source implementations.
- Artisan command `php artisan media:generate` supporting interactive and scripted workflows (`--model`, `--count`, `--source`, `--collection`, `--concurrent`, `--batch-size`).
- Concurrent image downloads and batch processing with progress bar.
- Database activity logging via `MediaGenerationLog` and migration.
- Programmatic API via `MediaGeneratorService`, `ModelGeneratorService`, and `MediaGenerator` facade.
- Support for PHP 8.1 through 8.5 and Laravel 10, 11, 12, and 13.
