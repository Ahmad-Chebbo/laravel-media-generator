<?php

declare(strict_types=1);

namespace AhmadChebbo\LaravelMediaGenerator\Tests\Unit;

use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;
use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\PicsumImageSource;
use AhmadChebbo\LaravelMediaGenerator\Tests\TestCase;
use Mockery;

class MediaGeneratorServiceTest extends TestCase
{
    private MediaGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaGeneratorService();
    }

    public function test_it_can_set_image_source(): void
    {
        $source = new PicsumImageSource();

        $result = $this->service->setImageSource($source);

        $this->assertSame($this->service, $result);
    }

    public function test_it_can_set_image_source_by_name(): void
    {
        $result = $this->service->setImageSource('picsum');

        $this->assertSame($this->service, $result);
    }

    public function test_it_throws_exception_for_invalid_source_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image source: invalid-source');

        $this->service->setImageSource('invalid-source');
    }

    public function test_it_can_generate_media_with_basic_config(): void
    {
        $config = [
            'count' => 2,
            'model_class' => \App\Models\User::class,
        ];

        $result = $this->service->generateMedia($config);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_it_can_generate_media_with_custom_dimensions(): void
    {
        $config = [
            'count' => 1,
            'model_class' => \App\Models\User::class,
            'width' => 800,
            'height' => 600,
        ];

        $result = $this->service->generateMedia($config);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_it_can_generate_media_with_model_fields(): void
    {
        $config = [
            'count' => 1,
            'model_class' => \App\Models\User::class,
            'model_fields' => [
                'name' => fn($index) => "Test User {$index}",
                'email' => fn($index) => "user{$index}@example.com",
            ],
        ];

        $result = $this->service->generateMedia($config);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_it_can_generate_media_with_progress_callback(): void
    {
        $progressCalled = false;

        $config = [
            'count' => 1,
            'model_class' => \App\Models\User::class,
            'progress_callback' => function ($current, $total) use (&$progressCalled) {
                $progressCalled = true;
                $this->assertEquals(1, $current);
                $this->assertEquals(1, $total);
            },
        ];

        $result = $this->service->generateMedia($config);

        $this->assertIsArray($result);
        $this->assertTrue($progressCalled);
    }

    public function test_it_throws_exception_for_invalid_model_class(): void
    {
        $config = [
            'count' => 1,
            'model_class' => 'Invalid\Model\Class',
        ];

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generateMedia($config);
    }

    public function test_it_throws_exception_for_negative_count(): void
    {
        $config = [
            'count' => -1,
            'model_class' => \App\Models\User::class,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Count must be a positive integer');

        $this->service->generateMedia($config);
    }

    public function test_it_throws_exception_for_zero_count(): void
    {
        $config = [
            'count' => 0,
            'model_class' => \App\Models\User::class,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Count must be a positive integer');

        $this->service->generateMedia($config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
