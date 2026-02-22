<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Builders;

use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;
use Subhashladumor1\LaravelAiDocs\Processors\PDFProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;
use Subhashladumor1\LaravelAiDocs\Services\AskPDFService;
use Subhashladumor1\LaravelAiDocs\Services\DocxService;
use Subhashladumor1\LaravelAiDocs\Services\JSONConversionService;
use Subhashladumor1\LaravelAiDocs\Services\MarkdownService;
use Subhashladumor1\LaravelAiDocs\Services\OCRService;
use Subhashladumor1\LaravelAiDocs\Services\PDFService;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;
use Subhashladumor1\LaravelAiDocs\Services\TableExtractionService;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;
use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;

/**
 * Fluent builder for general document operations (DOCX, TXT, etc.).
 * PDF-specific pipelines are handled by PDFBuilder.
 */
class DocumentBuilder
{
    private string $rawText = '';

    private string $summary = '';

    private string $markdown = '';

    private array $jsonData = [];

    /** @var TableDTO[] */
    private array $tables = [];

    private ?string $language = null;

    private float $startTime;

    public function __construct(
        private readonly string $filePath,
        private readonly AIProviderInterface $provider,
        private readonly FileValidator $fileValidator,
        private readonly LanguageDetector $languageDetector,
        private readonly PDFService $pdfService,
        private readonly DocxService $docxService,
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
        $this->rawText = $this->extractRawText();
    }

    /**
     * Override detected language.
     */
    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Auto-detect and enhance the text (placeholder for further enhancement steps).
     */
    public function enhance(): static
    {
        // Enhancement hooks can be injected here in extended builders.
        return $this;
    }

    /**
     * Generate a summary.
     */
    public function summarize(?string $prompt = null): static
    {
        $this->summary = $this->summarizerService->summarize(
            $this->provider,
            $this->rawText,
            $this->resolvedLanguage(),
            $prompt,
        );

        return $this;
    }

    /**
     * Extract tables.
     */
    public function tables(): static
    {
        $this->tables = $this->tableService->extract($this->provider, $this->rawText);

        return $this;
    }

    /**
     * Convert to Markdown.
     */
    public function toMarkdown(): string
    {
        $this->markdown = $this->markdownService->build(
            title: basename($this->filePath),
            summary: $this->summary,
            tables: $this->tables,
            rawText: $this->rawText,
        );

        return $this->markdown;
    }

    /**
     * Convert to structured JSON.
     *
     * @return array<string, mixed>
     */
    public function toJson(?string $prompt = null): array
    {
        $this->jsonData = $this->jsonService->convert($this->provider, $this->rawText, $prompt);

        return $this->jsonData;
    }

    /**
     * Ask a question about this document.
     */
    public function ask(string $question): string
    {
        return $this->askService->ask($this->provider, $this->rawText, $question);
    }

    /**
     * Return raw extracted text.
     */
    public function text(): string
    {
        return $this->rawText;
    }

    /**
     * Build a full DocumentResultDTO.
     */
    public function result(): DocumentResultDTO
    {
        return new DocumentResultDTO(
            rawText: $this->rawText,
            summary: $this->summary,
            markdown: $this->markdown,
            json: $this->jsonData,
            tables: $this->tables,
            language: $this->resolvedLanguage(),
            mimeType: $this->fileValidator->mimeType($this->filePath),
            filePath: $this->filePath,
            provider: $this->provider->getProviderName(),
            model: $this->provider->getCurrentModel(),
            processingTimeSeconds: microtime(true) - $this->startTime,
        );
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    private function extractRawText(): string
    {
        $ext = $this->fileValidator->extension($this->filePath);

        return match ($ext) {
            'pdf' => $this->pdfService->extractText($this->provider, $this->filePath, $this->language),
            'docx', 'doc' => $this->docxService->extractText($this->filePath),
            'txt', 'md' => file_get_contents($this->filePath) ?: '',
            default => file_get_contents($this->filePath) ?: '',
        };
    }

    private function resolvedLanguage(): string
    {
        return $this->language ?? $this->languageDetector->detect($this->rawText);
    }
}
