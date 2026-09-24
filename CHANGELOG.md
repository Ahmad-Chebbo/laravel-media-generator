# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2026-09-24

### Fixed
- Model class validation and resolution by calling resolveModelClass method in `askForRecordId` method in the `GenerateMediaCommand` class.

## [1.0.2] - 2026-09-24

### Added
- New **DiceBear** image source (`DicebearImageSource`) using the [DiceBear HTTP API](https://www.dicebear.com/how-to-use/http-api/) (v10.x).
  - Generates deterministic raster avatars (JPG) with a seed derived from the image index.
  - Supports all 61 official DiceBear styles (e.g. `bottts`, `pixel-art`, `lorelei`, `avataaars`). Default style: `bottts`.
  - Style is configurable via `config/media-generator.php` (`sources.dicebear.style`) or interactively at command runtime.
- `--dicebear-style=` option on `php artisan media:generate` to specify the style without interaction.
- `askForDicebearStyle()` interactive style picker in `GenerateMediaCommand` with 12 popular presets and a free-text fallback.
- `MediaGeneratorService::setDicebearStyle(string $style)` for programmatic style overriding.

## [1.0.1] - 2026-09-24

### Fixed
- Model class validation and resolution by adding a new method `resolveModelClass` to the `GenerateMediaCommand` class.

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


