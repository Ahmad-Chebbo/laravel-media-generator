# Contributing to Laravel Media Generator

Thank you for your interest in contributing to Laravel Media Generator! This document provides guidelines and information for contributors.

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Laravel 9.0 or higher
- Composer
- Git

### Development Setup

1. **Fork the repository**

    ```bash
    git clone https://github.com/AhmadChebbo/laravel-media-generator.git
    cd laravel-media-generator
    ```

2. **Install dependencies**

    ```bash
    composer install
    ```

3. **Set up testing environment**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

## Development Guidelines

### Code Style

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use strict typing: `declare(strict_types=1);`
- Follow Laravel conventions and best practices
- Use descriptive variable and method names
- Add proper PHPDoc comments for public methods

### Architecture Principles

- Follow SOLID principles
- Use dependency injection
- Implement proper error handling with custom exceptions
- Write testable code
- Keep methods focused and single-purpose

### File Structure

```
src/
├── Commands/
│   └── GenerateMediaCommand.php
├── Contracts/
│   └── ImageSourceInterface.php
├── Exceptions/
│   └── MediaGeneratorException.php
├── Services/
│   ├── MediaGeneratorService.php
│   └── ImageSources/
│       ├── PicsumImageSource.php
│       └── PlaceholderImageSource.php
└── MediaGeneratorServiceProvider.php
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test -- --coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/MediaGeneratorServiceTest.php
```

### Writing Tests

- Write unit tests for all new features
- Test both success and failure scenarios
- Mock external dependencies
- Use descriptive test method names
- Follow AAA pattern (Arrange, Act, Assert)

### Test Structure

```php
<?php

declare(strict_types=1);

namespace AhmadChebbo\LaravelMediaGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;

class MediaGeneratorServiceTest extends TestCase
{
    public function test_it_can_generate_media(): void
    {
        // Arrange
        $service = new MediaGeneratorService();

        // Act
        $result = $service->generateMedia(['count' => 1]);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }
}
```

## Pull Request Process

### Before Submitting

1. **Create a feature branch**

    ```bash
    git checkout -b feature/your-feature-name
    ```

2. **Make your changes**
    - Follow the coding standards
    - Add tests for new functionality
    - Update documentation if needed

3. **Run tests**

    ```bash
    composer test
    composer check-style
    ```

4. **Commit your changes**
    ```bash
    git add .
    git commit -m "feat: add new image source support"
    ```

### Commit Message Format

Use conventional commit format:

- `feat:` for new features
- `fix:` for bug fixes
- `docs:` for documentation changes
- `style:` for code style changes
- `refactor:` for code refactoring
- `test:` for test additions
- `chore:` for maintenance tasks

### Pull Request Guidelines

1. **Title**: Use a clear, descriptive title
2. **Description**: Explain what the PR does and why
3. **Tests**: Ensure all tests pass
4. **Documentation**: Update README or docs if needed
5. **Screenshots**: Include screenshots for UI changes

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

- Laravel version
- PHP version
- Package version
- Steps to reproduce
- Expected vs actual behavior
- Error messages or stack traces

### Feature Requests

For feature requests, please include:

- Use case description
- Expected functionality
- Potential implementation approach
- Benefits to the community

## Code Review Process

1. **Automated Checks**: All PRs must pass CI checks
2. **Review**: At least one maintainer must approve
3. **Testing**: Changes must be tested in a Laravel application
4. **Documentation**: New features must be documented

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

- [ ] All tests pass
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated
- [ ] Version is bumped in composer.json
- [ ] Tag is created and pushed

## Community Guidelines

### Code of Conduct

- Be respectful and inclusive
- Help others learn and grow
- Provide constructive feedback
- Follow the project's coding standards

### Communication

- Use GitHub Issues for bug reports and feature requests
- Use GitHub Discussions for questions and ideas
- Be patient and helpful with newcomers

## Getting Help

- **Documentation**: Check the README.md first
- **Issues**: Search existing issues before creating new ones
- **Discussions**: Use GitHub Discussions for questions
- **Email**: Contact maintainers for private matters

## License

By contributing to Laravel Media Generator, you agree that your contributions will be licensed under the MIT License.

Thank you for contributing to Laravel Media Generator! 🚀
