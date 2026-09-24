<?php

namespace AhmadChebbo\LaravelMediaGenerator\Services;

use AhmadChebbo\LaravelMediaGenerator\Exceptions\MediaGeneratorException;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\InteractsWithMedia;

class ModelGeneratorService
{
    private $command;

    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Generate a model record with interactive field input or fast generation
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function generateModelRecord(string $modelClass, ?array $modelFields = null, bool $interactiveMode = true)
    {
        $model = new $modelClass;

        if ($interactiveMode) {
            return $this->generateInteractiveRecord($model, $modelFields);
        }

        return $this->generateFastRecord($model, $modelFields);
    }

    /**
     * Generate a record with interactive field input
     *
     * @param  mixed  $model
     * @return mixed
     */
    public function generateInteractiveRecord($model, ?array $modelFields = null)
    {
        if ($this->command) {
            $this->command->info('📝 Interactive Mode - Please provide field values for the new record of '.$model->getTable().':');
            $this->command->newLine();
        }

        $fillableFields = $this->getFillableFields($model);

        foreach ($fillableFields as $field) {
            $value = $this->getInteractiveFieldValue($field, $modelFields[$field] ?? null);
            $model->{$field} = $value;
        }

        $model->save();

        if ($this->command) {
            $this->command->info("✅ Record created with ID: {$model->id}");
        }

        return $model;
    }

    /**
     * Generate a record with fast/fake data
     *
     * @param  mixed  $model
     * @return mixed
     */
    public function generateFastRecord($model, ?array $modelFields = null)
    {
        $fillableFields = $this->getFillableFields($model);

        foreach ($fillableFields as $field) {
            $value = $this->generateFieldValue($field, $modelFields[$field] ?? null);
            $model->{$field} = $value;
        }

        $model->save();

        if ($this->command) {
            $this->command->info("✅ Fast record created with ID: {$model->id}");
        }

        return $model;
    }

    /**
     * Get fillable fields from model
     *
     * @param  mixed  $model
     */
    public function getFillableFields($model): array
    {
        $fillable = $model->getFillable();

        // Filter out common fields that shouldn't be filled
        $excludedFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

        return array_filter($fillable, function ($field) use ($excludedFields) {
            return ! in_array($field, $excludedFields);
        });
    }

    /**
     * Get interactive field value from user input
     *
     * @param  mixed  $defaultValue
     * @return mixed
     */
    public function getInteractiveFieldValue(string $field, $defaultValue = null)
    {
        if (! $this->command) {
            return $defaultValue ?? $this->generateFieldValue($field);
        }

        $fieldType = $this->getFieldType($field);
        $description = $this->getFieldDescription($field, $fieldType);

        $question = "Enter value for '{$field}' ({$fieldType})";
        if ($description) {
            $question .= " - {$description}";
        }

        if ($defaultValue !== null) {
            $question .= " [default: {$defaultValue}]";
        }

        $value = $this->command->ask($question, $defaultValue);

        return $value;
    }

    /**
     * Generate field value based on field type and name
     *
     * @param  mixed  $defaultValue
     * @return mixed
     */
    public function generateFieldValue(string $field, $defaultValue = null)
    {
        if ($defaultValue !== null) {
            return $defaultValue;
        }

        $fieldType = $this->getFieldType($field);
        $fieldName = strtolower($field);

        // Generate based on field name patterns
        if (str_contains($fieldName, 'name')) {
            return fake()->name();
        }

        if (str_contains($fieldName, 'email')) {
            return fake()->email();
        }

        if (str_contains($fieldName, 'phone')) {
            return fake()->phoneNumber();
        }

        if (str_contains($fieldName, 'title')) {
            return fake()->sentence(3);
        }

        if (str_contains($fieldName, 'description') || str_contains($fieldName, 'content')) {
            return fake()->paragraph();
        }

        if (str_contains($fieldName, 'address')) {
            return fake()->address();
        }

        if (str_contains($fieldName, 'url') || str_contains($fieldName, 'link')) {
            return fake()->url();
        }

        if (str_contains($fieldName, 'price') || str_contains($fieldName, 'cost')) {
            return fake()->randomFloat(2, 1, 1000);
        }

        if (str_contains($fieldName, 'quantity') || str_contains($fieldName, 'count')) {
            return fake()->numberBetween(1, 100);
        }

        if (str_contains($fieldName, 'status')) {
            return fake()->randomElement(['active', 'inactive', 'pending', 'completed']);
        }

        if (str_contains($fieldName, 'type')) {
            return fake()->randomElement(['type_a', 'type_b', 'type_c']);
        }

        // Generate based on field type
        return $this->generateValueByType($fieldType);
    }

    /**
     * Generate value based on field type
     *
     * @return mixed
     */
    public function generateValueByType(string $fieldType)
    {
        return match ($fieldType) {
            'string', 'varchar', 'text' => fake()->sentence(),
            'integer', 'int', 'bigint' => fake()->numberBetween(1, 1000),
            'decimal', 'float', 'double' => fake()->randomFloat(2, 0, 1000),
            'boolean', 'bool' => fake()->boolean(),
            'date' => fake()->date(),
            'datetime', 'timestamp' => fake()->dateTime()->format('Y-m-d H:i:s'),
            'time' => fake()->time(),
            'json' => json_encode(['key' => 'value']),
            default => fake()->sentence(),
        };
    }

    /**
     * Get field type based on field name and common patterns
     */
    public function getFieldType(string $field, $model = null): string
    {
        // Get the model database table
        // $tableName = $model->getTable();
        // $fieldType = $model->getConnection()->getDoctrineColumn($tableName, $field)->getType()->getName();
        // return $fieldType;

        $fieldName = strtolower($field);

        // Common field type patterns
        if (str_contains($fieldName, 'email')) {
            return 'email';
        }
        if (str_contains($fieldName, 'phone')) {
            return 'phone';
        }
        if (str_contains($fieldName, 'url') || str_contains($fieldName, 'link')) {
            return 'url';
        }
        if (str_contains($fieldName, 'price') || str_contains($fieldName, 'cost')) {
            return 'decimal';
        }
        if (str_contains($fieldName, 'quantity') || str_contains($fieldName, 'count')) {
            return 'integer';
        }
        if (str_contains($fieldName, 'status')) {
            return 'string';
        }
        if (str_contains($fieldName, 'type')) {
            return 'string';
        }
        if (str_contains($fieldName, 'date') || str_contains($fieldName, '_at')) {
            return 'date';
        }
        if (str_contains($fieldName, 'time')) {
            return 'time';
        }
        if (str_contains($fieldName, 'is_') || str_contains($fieldName, 'has_')) {
            return 'boolean';
        }

        // Default to string for most fields
        return 'string';
    }

    /**
     * Get field description for interactive mode
     */
    public function getFieldDescription(string $field, string $fieldType): string
    {
        $fieldName = strtolower($field);

        return match (true) {
            str_contains($fieldName, 'name') => 'Full name of the person/entity',
            str_contains($fieldName, 'email') => 'Valid email address',
            str_contains($fieldName, 'phone') => 'Phone number',
            str_contains($fieldName, 'title') => 'Title or heading',
            str_contains($fieldName, 'description') => 'Detailed description',
            str_contains($fieldName, 'content') => 'Main content text',
            str_contains($fieldName, 'address') => 'Full address',
            str_contains($fieldName, 'url') => 'Website URL',
            str_contains($fieldName, 'price') => 'Price amount',
            str_contains($fieldName, 'status') => 'Current status',
            str_contains($fieldName, 'type') => 'Type classification',
            default => ucfirst($fieldType).' value',
        };
    }

    /**
     * Validate if the model uses the Spatie MediaLibrary trait
     *
     * @throws MediaGeneratorException
     */
    public function validateModelUsesMediaLibrary(string $modelClass): void
    {
        if (! class_exists($modelClass)) {
            throw new MediaGeneratorException("Model class '{$modelClass}' does not exist.");
        }

        $model = new $modelClass;

        if (! method_exists($model, 'addMediaFromUrl')) {
            throw new MediaGeneratorException("Model '{$modelClass}' must use the Spatie MediaLibrary trait. ".
                "Add 'use Spatie\MediaLibrary\HasMedia;' and 'use Spatie\MediaLibrary\InteractsWithMedia;' to your model.");
        }

        if (! in_array(InteractsWithMedia::class, class_uses_recursive($model))) {
            throw new MediaGeneratorException("Model '{$modelClass}' must implement the InteractsWithMedia trait from Spatie MediaLibrary.");
        }
    }

    /**
     * Get all fields from the model
     */
    public function getModelFields(string $modelClass): array
    {
        // Get all fields from the model
        $model = new $modelClass;
        $fields = $model->getFillable();

        return $fields;
    }

    /**
     * Validate if the record exists
     */
    public function validateRecordExists(string $modelClass, int $recordId): mixed
    {
        $model = new $modelClass;
        $record = $model->find($recordId);

        if (! $record) {
            throw new MediaGeneratorException("Record with ID {$recordId} not found in {$modelClass}!");
        }

        return $record;
    }
}
