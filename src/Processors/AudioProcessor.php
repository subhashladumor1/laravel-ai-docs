<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Processors;

use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

/**
 * Validates and prepares audio files before Whisper-style transcription.
 */
class AudioProcessor
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Validate the audio file and return its absolute path.
     *
     * @throws FileProcessingException
     */
    public function prepare(string $filePath): string
    {
        if (!($this->config['enabled'] ?? true)) {
            throw new FileProcessingException('Audio processing is disabled in configuration.');
        }

        if (!file_exists($filePath)) {
            throw new FileProcessingException("Audio file not found: {$filePath}");
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $supported = $this->config['supported_formats'] ?? [
            'mp3',
            'mp4',
            'mpeg',
            'mpga',
            'm4a',
            'wav',
            'webm',
            'ogg',
        ];

        if (!in_array($ext, $supported, true)) {
            throw new FileProcessingException(
                "Unsupported audio format '.{$ext}'. Supported: " . implode(', ', $supported)
            );
        }

        $maxMb = (int) ($this->config['max_file_size_mb'] ?? 25);
        $maxBytes = $maxMb * 1_048_576;

        if (filesize($filePath) > $maxBytes) {
            throw new FileProcessingException(
                "Audio file exceeds maximum size of {$maxMb} MB: {$filePath}"
            );
        }

        return realpath($filePath);
    }

    /**
     * Return an appropriate MIME type for transmission.
     */
    public function mimeType(string $filePath): string
    {
        return match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'mp3', 'mpga', 'mpeg' => 'audio/mpeg',
            'mp4', 'm4a' => 'audio/mp4',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            default => 'audio/mpeg',
        };
    }
}
