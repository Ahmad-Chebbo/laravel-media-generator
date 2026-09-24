<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services;

use AhmadChebbo\LaravelMediaGenerator\Contracts\ImageSourceInterface;
use AhmadChebbo\LaravelMediaGenerator\Models\MediaGenerationLog;
use Illuminate\Support\Str;

class LoggingMediaGeneratorService extends MediaGeneratorService
{
    private string $batchId;

    private ImageSourceInterface $imageSource;

    public function __construct(ImageSourceInterface $imageSource)
    {
        parent::__construct();
        $this->imageSource = $imageSource;
    }

    public function generateMedia(array $options): array
    {
        $this->batchId = Str::uuid()->toString();

        $startTime = microtime(true);
        $results = parent::generateMedia($options);
        $endTime = microtime(true);

        // Log batch summary
        $this->logBatchSummary($options, $results, $endTime - $startTime);

        return $results;
    }

    public function saveAndAttachMedia(string $imageContent, array $imageData, array $options, int $index): bool
    {
        $startTime = microtime(true);
        $success = parent::saveAndAttachMedia($imageContent, $imageData, $options, $index);
        $endTime = microtime(true);

        if ($success) {
            $this->logMediaGeneration($imageData, $options, $index, $endTime - $startTime);
        }

        return $success;
    }

    private function logMediaGeneration(array $imageData, array $options, int $index, float $generationTime): void
    {
        MediaGenerationLog::create([
            'model_type' => $options['model_class'],
            'model_id' => $index, // This would need to be the actual model ID
            'collection_name' => $options['collection'] ?? 'default',
            'image_source' => $this->imageSource->getName(),
            'batch_id' => $this->batchId,
            'file_name' => $options['file_name'] ?? "generated_media_{$index}.jpg",
            'file_size' => strlen(file_get_contents($imageData['tempFile']) ?: ''),
            'dimensions' => $imageData['dimensions'],
            'generation_time' => $generationTime,
            'metadata' => [
                'url' => $imageData['url'],
                'index' => $index,
            ],
        ]);
    }

    private function logBatchSummary(array $options, array $results, float $totalTime): void
    {
        \Log::info('Media generation batch completed', [
            'batch_id' => $this->batchId,
            'model_class' => $options['model_class'],
            'total_requested' => $results['total'],
            'successful' => $results['success'],
            'errors' => $results['errors'],
            'success_rate' => round(($results['success'] / $results['total']) * 100, 2),
            'total_time' => $totalTime,
            'average_time_per_image' => round($totalTime / $results['total'], 3),
        ]);
    }
}
