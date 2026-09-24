<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;
use AhmadChebbo\LaravelMediaGenerator\Exceptions\MediaGeneratorException;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MediaGeneratorService
{
    private array $config;

    private ImageSourceInterface $imageSource;

    private Client $httpClient;

    private ?Command $command = null;

    public function __construct()
    {
        $this->config = config('media-generator');
        $this->setupHttpClient();
    }

    public function setImageSource(string $sourceName): self
    {
        $sourceConfig = $this->config['sources'][$sourceName] ?? null;

        if (! $sourceConfig) {
            throw new MediaGeneratorException("Image source '{$sourceName}' not found in configuration");
        }

        $this->imageSource = app($sourceConfig['class']);

        return $this;
    }

    public function setCommand(Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function generateMedia(array $options): array
    {
        $this->validateOptions($options);

        $totalImages = $options['count'];
        $batchSize = $options['batch_size'] ?? $this->config['defaults']['batch_size'];
        $totalBatches = ceil($totalImages / $batchSize);

        $results = [
            'total' => $totalImages,
            'success' => 0,
            'errors' => 0,
            'batches' => $totalBatches,
            'start_time' => microtime(true),
        ];

        $tempDir = $this->createTempDirectory();

        try {
            for ($batch = 0; $batch < $totalBatches; $batch++) {
                $batchStart = $batch * $batchSize + 1;
                $batchEnd = min(($batch + 1) * $batchSize, $totalImages);

                $batchResults = $this->processBatch($batchStart, $batchEnd, $options, $tempDir);

                $results['success'] += $batchResults['success'];
                $results['errors'] += $batchResults['errors'];

                if (isset($options['progress_callback']) && is_callable($options['progress_callback'])) {
                    $options['progress_callback']($batchEnd, $totalImages);
                }
            }
        } finally {
            $this->cleanupTempDirectory($tempDir);
        }

        $results['end_time'] = microtime(true);
        $results['duration'] = $results['end_time'] - $results['start_time'];

        return $results;
    }

    private function processBatch(int $start, int $end, array $options, string $tempDir): array
    {
        $promises = [];
        $imageData = [];

        // Create download promises
        for ($i = $start; $i <= $end; $i++) {
            $dimensions = $this->generateRandomDimensions();
            $imageUrl = $this->imageSource->generateUrl($dimensions['width'], $dimensions['height'], $i);
            $tempFile = $tempDir.'/media_'.$i.'_'.time().'.jpg';

            $imageData[$i] = [
                'url' => $imageUrl,
                'tempFile' => $tempFile,
                'dimensions' => $dimensions,
            ];

            $promises[$i] = $this->httpClient->getAsync($imageUrl);
        }

        // Execute concurrent downloads
        $responses = Utils::settle($promises)->wait();

        $success = 0;
        $errors = 0;

        // Process responses
        foreach ($responses as $index => $response) {
            try {
                if ($response['state'] === 'fulfilled') {
                    $httpResponse = $response['value'];
                    $imageContent = $httpResponse->getBody()->getContents();

                    if (! empty($imageContent) && $this->saveAndAttachMedia($imageContent, $imageData[$index], $options, $index)) {
                        $success++;
                        Log::info("Media generated and attached to model {$options['model_class']} with index {$index}");
                    } else {
                        $errors++;
                        Log::error("Failed to save and attach media to model {$options['model_class']} with index {$index} - Empty content or save operation failed");
                    }
                } else {
                    $errors++;
                    $errorReason = $response['reason'] ?? 'Unknown error';
                    Log::error("Failed to download media for model {$options['model_class']} with index {$index} - {$errorReason}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->cleanupTempFile($imageData[$index]['tempFile'] ?? null);
                Log::error("Exception occurred while processing media for model {$options['model_class']} with index {$index}: ".$e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }

    protected function saveAndAttachMedia(string $imageContent, array $imageData, array $options, int $index): bool
    {
        $tempFile = $imageData['tempFile'];

        if (file_put_contents($tempFile, $imageContent) === false) {
            return false;
        }

        try {
            // Get model instance (either existing or new)
            // $model = $this->getModelInstance($options, $index);
            $model = $options['model_instance'];

            // Add media to collection
            $mediaName = $options['media_name'] ?? "Generated Media {$index}";
            $fileName = $options['file_name'] ?? "generated_media_{$index}.jpg";
            $collection = $options['collection'] ?? 'default';

            $media = $model->addMedia($tempFile)
                ->usingName($mediaName)
                ->usingFileName($fileName)
                ->toMediaCollection($collection);

            return $media !== null;
        } finally {
            $this->cleanupTempFile($tempFile);
        }
    }

    private function getModelInstance(array $options, int $index): Model
    {
        // If record_id is provided, use existing record
        if (isset($options['record_id']) && $options['record_id'] !== null) {
            return $this->getExistingModelInstance($options);
        }

        // Otherwise, create new model instance
        return $this->createModelInstance($options, $index);
    }

    private function getExistingModelInstance(array $options): Model
    {
        $modelClass = $options['model_class'];
        $recordId = $options['record_id'];

        $model = $modelClass::find($recordId);

        if (! $model) {
            throw new MediaGeneratorException("Record with ID {$recordId} not found in {$modelClass}");
        }

        return $model;
    }

    private function createModelInstance(array $options, int $index): Model
    {
        $modelClass = $options['model_class'];
        $model = new $modelClass;

        // Set model fields
        if (isset($options['model_fields']) && is_array($options['model_fields'])) {
            foreach ($options['model_fields'] as $field => $value) {
                if (is_numeric($field)) {
                    $fieldName = $value;
                    $model->$fieldName = $this->generateInteractiveValue($fieldName, $index, $modelClass);
                } elseif (is_callable($value)) {
                    $model->$field = $value($index, $model);
                } else {
                    $model->$field = $value;
                }
            }
        }

        $model->save();

        return $model;
    }

    private function generateInteractiveValue(string $fieldName, int $index, string $modelClass): mixed
    {
        // Show field information
        $this->command->info("📝 Field: {$fieldName}");

        $choices = [
            'custom' => 'Enter custom value',
            'skip' => 'Skip this field (leave empty)',
        ];

        $choice = $this->command->choice("How to fill '{$fieldName}' for record {$index}?", $choices, 'auto');

        switch ($choice) {
            case 'custom':
                return $this->command->ask("Enter value for '{$fieldName}':");
            case 'skip':
                return null;
            default:
                return null;
        }
    }

    private function validateOptions(array $options): void
    {
        $required = ['count', 'model_class'];

        foreach ($required as $field) {
            if (! isset($options[$field])) {
                throw new MediaGeneratorException("Required option '{$field}' is missing");
            }
        }

        if (! class_exists($options['model_class'])) {
            throw new MediaGeneratorException("Model class '{$options['model_class']}' does not exist");
        }

        if (! is_subclass_of($options['model_class'], Model::class)) {
            throw new MediaGeneratorException('Model class must extend Illuminate\\Database\\Eloquent\\Model');
        }
    }

    private function generateRandomDimensions(): array
    {
        return [
            'width' => rand($this->config['dimensions']['min_width'], $this->config['dimensions']['max_width']),
            'height' => rand($this->config['dimensions']['min_height'], $this->config['dimensions']['max_height']),
        ];
    }

    private function setupHttpClient(): void
    {
        $this->httpClient = new Client([
            'timeout' => $this->config['defaults']['timeout'],
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Laravel-Media-Generator)',
            ],
        ]);
    }

    private function createTempDirectory(): string
    {
        $tempDir = storage_path('app/'.$this->config['storage']['temp_directory']);

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir;
    }

    private function cleanupTempDirectory(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $files = glob($tempDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tempDir);
        }
    }

    private function cleanupTempFile(?string $tempFile): void
    {
        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    /**
     * Validate PHP memory and execution time for media generation
     *
     * @param  int  $count  Number of media files to generate
     * @param  array  $options  Generation options
     * @return array Validation result with recommendations
     */
    public function validateSystemRequirements(int $count, array $options = []): array
    {
        $currentMemoryLimit = $this->getCurrentMemoryLimit();
        $currentMaxExecutionTime = $this->getCurrentMaxExecutionTime();
        $estimatedMemoryUsage = $this->estimateMemoryUsage($count, $options);
        $estimatedExecutionTime = $this->estimateExecutionTime($count, $options);

        $recommendations = [];
        $warnings = [];

        // Memory validation
        if ($estimatedMemoryUsage > $currentMemoryLimit) {
            $recommendedMemory = $this->calculateRecommendedMemory($estimatedMemoryUsage);
            $recommendations[] = "Increase memory_limit to at least {$recommendedMemory}";
            $warnings[] = "Current memory limit ({$currentMemoryLimit}) may be insufficient for {$count} media files";
        }

        // Execution time validation
        if ($estimatedExecutionTime > $currentMaxExecutionTime) {
            $recommendedTime = $this->calculateRecommendedExecutionTime($estimatedExecutionTime);
            $recommendations[] = "Increase max_execution_time to at least {$recommendedTime} seconds";
            $warnings[] = "Current execution time limit ({$currentMaxExecutionTime}s) may be insufficient for {$count} media files";
        }

        return [
            'valid' => empty($warnings),
            'current_memory_limit' => $currentMemoryLimit,
            'current_max_execution_time' => $currentMaxExecutionTime,
            'estimated_memory_usage' => $estimatedMemoryUsage,
            'estimated_execution_time' => $estimatedExecutionTime,
            'recommendations' => $recommendations,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get current memory limit in bytes
     */
    private function getCurrentMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return PHP_INT_MAX; // Unlimited
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'k' => $value * 1024,
            'm' => $value * 1024 * 1024,
            'g' => $value * 1024 * 1024 * 1024,
            default => $value,
        };
    }

    /**
     * Get current max execution time in seconds
     */
    private function getCurrentMaxExecutionTime(): int
    {
        $maxExecutionTime = ini_get('max_execution_time');

        return $maxExecutionTime === '0' ? PHP_INT_MAX : (int) $maxExecutionTime;
    }

    /**
     * Estimate memory usage for media generation
     *
     * @return int Estimated memory usage in bytes
     */
    private function estimateMemoryUsage(int $count, array $options): int
    {
        $baseMemory = 50 * 1024 * 1024; // 50MB base memory
        $perImageMemory = 5 * 1024 * 1024; // 5MB per image (download + processing)
        $concurrentFactor = $options['concurrent'] ?? 10;

        // Additional memory for concurrent operations
        $concurrentMemory = $perImageMemory * $concurrentFactor;

        return $baseMemory + ($perImageMemory * $count) + $concurrentMemory;
    }

    /**
     * Estimate execution time for media generation
     *
     * @return int Estimated time in seconds
     */
    private function estimateExecutionTime(int $count, array $options): int
    {
        $baseTime = 5; // 5 seconds base time
        $perImageTime = 2; // 2 seconds per image (download + processing)
        $concurrentFactor = $options['concurrent'] ?? 10;

        // Calculate time with concurrency
        $sequentialTime = $perImageTime * $count;
        $concurrentTime = ceil($sequentialTime / $concurrentFactor);

        return $baseTime + $concurrentTime;
    }

    /**
     * Calculate recommended memory limit
     *
     * @return string Recommended memory limit (e.g., "512M")
     */
    private function calculateRecommendedMemory(int $estimatedUsage): string
    {
        $recommended = $estimatedUsage * 1.5; // Add 50% buffer

        if ($recommended >= 1024 * 1024 * 1024) {
            return ceil($recommended / (1024 * 1024 * 1024)).'G';
        }

        return ceil($recommended / (1024 * 1024)).'M';
    }

    /**
     * Calculate recommended execution time
     *
     * @return int Recommended execution time in seconds
     */
    private function calculateRecommendedExecutionTime(int $estimatedTime): int
    {
        return (int) ceil($estimatedTime * 1.5); // Add 50% buffer
    }

    /**
     * Display system requirements validation results
     */
    public function displayValidationResults(array $validation): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info('🔍 System Requirements Validation');
        $this->command->info('================================');

        $this->command->table(
            ['Setting', 'Current', 'Estimated', 'Status'],
            [
                [
                    'Memory Limit',
                    $this->formatBytes($validation['current_memory_limit']),
                    $this->formatBytes($validation['estimated_memory_usage']),
                    $validation['current_memory_limit'] >= $validation['estimated_memory_usage'] ? '✅ OK' : '⚠️ Low',
                ],
                [
                    'Execution Time',
                    $validation['current_max_execution_time'] === PHP_INT_MAX ? 'Unlimited' : $validation['current_max_execution_time'].'s',
                    $validation['estimated_execution_time'].'s',
                    $validation['current_max_execution_time'] >= $validation['estimated_execution_time'] ? '✅ OK' : '⚠️ Low',
                ],
            ]
        );

        if (! empty($validation['warnings'])) {
            $this->command->warn('⚠️  Warnings:');
            foreach ($validation['warnings'] as $warning) {
                $this->command->warn("  • {$warning}");
            }
        }

        $this->command->newLine();

        if (! empty($validation['recommendations'])) {
            $this->command->info('💡 Recommendations:');
            foreach ($validation['recommendations'] as $recommendation) {
                $this->command->info("  • {$recommendation}");
            }

            $this->command->newLine();

            $this->command->info('📝 Search for solutions:');
            $this->command->info('  • https://www.google.com/search?q='.urlencode('php memory limit '.$validation['warnings'][0]));
            $this->command->info('  • https://www.google.com/search?q='.urlencode('php max execution time '.($validation['warnings'][1] ?? $validation['recommendations'][0])));
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === PHP_INT_MAX) {
            return 'Unlimited';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Check if system can handle the media generation
     */
    public function canHandleGeneration(int $count, array $options = []): bool
    {
        $validation = $this->validateSystemRequirements($count, $options);

        return $validation['valid'];
    }
}
