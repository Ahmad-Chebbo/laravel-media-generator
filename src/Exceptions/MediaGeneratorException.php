<?php

namespace AhmadChebbo\LaravelMediaGenerator\Exceptions;

use Exception;

class MediaGeneratorException extends Exception
{
    /**
     * Create a new MediaGeneratorException instance.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a new MediaGeneratorException for configuration errors.
     */
    public static function configurationError(string $configKey): static
    {
        return new static("Configuration error: '{$configKey}' is not properly configured");
    }

    /**
     * Create a new MediaGeneratorException for image source errors.
     */
    public static function imageSourceError(string $sourceName, string $reason = ''): static
    {
        $message = "Image source '{$sourceName}' error";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new static($message);
    }

    /**
     * Create a new MediaGeneratorException for validation errors.
     */
    public static function validationError(string $field, string $message): static
    {
        return new static("Validation error for '{$field}': {$message}");
    }

    /**
     * Create a new MediaGeneratorException for file operation errors.
     */
    public static function fileOperationError(string $operation, string $filePath, string $reason = ''): static
    {
        $message = "File operation '{$operation}' failed for '{$filePath}'";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new static($message);
    }

    /**
     * Create a new MediaGeneratorException for HTTP request errors.
     */
    public static function httpRequestError(string $url, int $statusCode, string $reason = ''): static
    {
        $message = "HTTP request failed for '{$url}' with status code {$statusCode}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new static($message);
    }
}
