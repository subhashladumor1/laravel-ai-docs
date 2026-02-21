<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Builders;

use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;
use Subhashladumor1\LaravelAiDocs\Processors\AudioProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;
use Subhashladumor1\LaravelAiDocs\Services\AudioService;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;
use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;

/**
 * Fluent builder for audio transcription.
 */
class AudioBuilder
{
    private string $transcript = '';

    private string $summary = '';

    private ?string $language = null;

    private float $startTime;

    public function __construct(
        private readonly string $filePath,
        private readonly AIProviderInterface $provider,
        private readonly FileValidator $fileValidator,
        private readonly LanguageDetector $languageDetector,
        private readonly AudioService $audioService,
        private readonly SummarizerService $summarizerService,
    ) {
        $this->startTime = microtime(true);
    }

    /**
     * Override transcription language hint.
     */
    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Transcribe the audio file to text.
     */
    public function transcribe(): string
    {
        $this->transcript = $this->audioService->transcribe(
            $this->provider,
            $this->filePath,
            $this->language,
        );

        return $this->transcript;
    }

    /**
     * Transcribe then summarize.
     */
    public function summarize(?string $prompt = null): string
    {
        if ($this->transcript === '') {
            $this->transcribe();
        }

        $this->summary = $this->summarizerService->summarize(
            $this->provider,
            $this->transcript,
            $this->language,
            $prompt,
        );

        return $this->summary;
    }

    /**
     * Build a DocumentResultDTO with the transcript.
     */
    public function result(): DocumentResultDTO
    {
        if ($this->transcript === '') {
            $this->transcribe();
        }

        return new DocumentResultDTO(
            rawText: $this->transcript,
            summary: $this->summary,
            language: $this->language ?? $this->languageDetector->detect($this->transcript),
            mimeType: $this->fileValidator->mimeType($this->filePath),
            filePath: $this->filePath,
            provider: $this->provider->getProviderName(),
            model: $this->provider->getCurrentModel(),
            processingTimeSeconds: microtime(true) - $this->startTime,
            transcript: $this->transcript,
        );
    }
}
