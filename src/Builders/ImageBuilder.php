<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Builders;

use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;
use Subhashladumor1\LaravelAiDocs\Processors\ImageProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;
use Subhashladumor1\LaravelAiDocs\Services\AskPDFService;
use Subhashladumor1\LaravelAiDocs\Services\JSONConversionService;
use Subhashladumor1\LaravelAiDocs\Services\MarkdownService;
use Subhashladumor1\LaravelAiDocs\Services\OCRService;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;
use Subhashladumor1\LaravelAiDocs\Services\TableExtractionService;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;
use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;

/**
 * Fluent builder for image-specific operations.
 * Supports OCR text extraction via AI vision models.
 */
class ImageBuilder
{
    private string $extractedText = '';

    private string $summary = '';

    private ?string $language = null;

    private float $startTime;

    public function __construct(
        private readonly string $filePath,
        private readonly AIProviderInterface $provider,
        private readonly FileValidator $fileValidator,
        private readonly LanguageDetector $languageDetector,
        private readonly ImageProcessor $imageProcessor,
        private readonly OCRService $ocrService,
        private readonly SummarizerService $summarizerService,
        private readonly TableExtractionService $tableService,
        private readonly AskPDFService $askService,
        private readonly MarkdownService $markdownService,
        private readonly JSONConversionService $jsonService,
        ?string $language = null,
    ) {
        $this->startTime = microtime(true);
        $this->language = $language;
    }

    /**
     * Override language for OCR.
     */
    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Extract text from the image using AI OCR.
     */
    public function text(?string $prompt = null): string
    {
        $this->extractedText = $this->ocrService->extractText(
            $this->provider,
            $this->filePath,
            $this->language,
            $prompt,
        );

        return $this->extractedText;
    }

    /**
     * Extract text, then summarize it.
     */
    public function summarize(?string $prompt = null): string
    {
        if ($this->extractedText === '') {
            $this->text();
        }

        $this->summary = $this->summarizerService->summarize(
            $this->provider,
            $this->extractedText,
            $this->language,
            $prompt,
        );

        return $this->summary;
    }

    /**
     * Extract tables from the image.
     *
     * @return TableDTO[]
     */
    public function tables(): array
    {
        if ($this->extractedText === '') {
            $this->text();
        }

        return $this->tableService->extract($this->provider, $this->extractedText);
    }

    /**
     * Ask a question about the image content.
     */
    public function ask(string $question): string
    {
        if ($this->extractedText === '') {
            $this->text();
        }

        return $this->askService->ask($this->provider, $this->extractedText, $question);
    }

    /**
     * Convert image text to structured JSON.
     *
     * @return array<string, mixed>
     */
    public function toJson(): array
    {
        if ($this->extractedText === '') {
            $this->text();
        }

        return $this->jsonService->convert($this->provider, $this->extractedText);
    }

    /**
     * Build a DocumentResultDTO.
     */
    public function result(): DocumentResultDTO
    {
        if ($this->extractedText === '') {
            $this->text();
        }

        return new DocumentResultDTO(
            rawText: $this->extractedText,
            summary: $this->summary,
            language: $this->language ?? $this->languageDetector->detect($this->extractedText),
            mimeType: $this->fileValidator->mimeType($this->filePath),
            filePath: $this->filePath,
            provider: $this->provider->getProviderName(),
            model: $this->provider->getCurrentModel(),
            processingTimeSeconds: microtime(true) - $this->startTime,
        );
    }
}
