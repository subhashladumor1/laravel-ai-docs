<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Support;

use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

/**
 * Validates uploaded files prior to processing.
 */
final class FileValidator
{
    private const MAX_FILE_SIZE_BYTES = 104_857_600; // 100 MB default

    /**
     * @param  array<string, string[]>  $supportedFormats  Category => extensions map.
     */
    public function __construct(
        private readonly array $supportedFormats,
        private readonly int $maxFileSizeBytes = self::MAX_FILE_SIZE_BYTES,
    ) {
    }

    /**
     * Validate that a file path is a valid, readable file of an accepted type.
     *
     * @throws FileProcessingException
     */
    public function validate(string $filePath, string $category): void
    {
        $this->assertExists($filePath);
        $this->assertReadable($filePath);
        $this->assertNotEmpty($filePath);
        $this->assertSize($filePath);
        $this->assertFormat($filePath, $category);
    }

    /**
     * @throws FileProcessingException
     */
    private function assertExists(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new FileProcessingException("File does not exist: {$filePath}");
        }
    }

    /**
     * @throws FileProcessingException
     */
    private function assertReadable(string $filePath): void
    {
        if (!is_readable($filePath)) {
            throw new FileProcessingException("File is not readable: {$filePath}");
        }
    }

    /**
     * @throws FileProcessingException
     */
    private function assertNotEmpty(string $filePath): void
    {
        if (filesize($filePath) === 0) {
            throw new FileProcessingException("File is empty: {$filePath}");
        }
    }

    /**
     * @throws FileProcessingException
     */
    private function assertSize(string $filePath): void
    {
        $size = filesize($filePath);

        if ($size > $this->maxFileSizeBytes) {
            $mb = round($this->maxFileSizeBytes / 1_048_576);
            throw new FileProcessingException(
                "File exceeds maximum allowed size of {$mb} MB: {$filePath}"
            );
        }
    }

    /**
     * @throws FileProcessingException
     */
    private function assertFormat(string $filePath, string $category): void
    {
        $allowed = $this->supportedFormats[$category] ?? [];

        if (empty($allowed)) {
            // No restriction configured for this category
            return;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            throw new FileProcessingException(
                "Unsupported file type '.{$ext}' for category '{$category}'. "
                . 'Supported: ' . implode(', ', $allowed)
            );
        }
    }

    /**
     * Return the extension (lowercase, no dot) for a file path.
     */
    public function extension(string $filePath): string
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }

    /**
     * Detect the MIME type of a file.
     */
    public function mimeType(string $filePath): string
    {
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
            if ($mime && $mime !== false) {
                return $mime;
            }
        }

        return match ($this->extension($filePath)) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            default => 'application/octet-stream',
        };
    }
}
