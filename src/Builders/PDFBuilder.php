<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Builders;

use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;
use Subhashladumor1\LaravelAiDocs\Processors\PDFProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;
use Subhashladumor1\LaravelAiDocs\Services\AskPDFService;
use Subhashladumor1\LaravelAiDocs\Services\JSONConversionService;
use Subhashladumor1\LaravelAiDocs\Services\MarkdownService;
use Subhashladumor1\LaravelAiDocs\Services\PDFService;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;
use Subhashladumor1\LaravelAiDocs\Services\TableExtractionService;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;
use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;

/**
 * Fluent builder specifically designed for PDF files.
 *
 * The full pipeline looks like:
 *   AIDocs::model('gpt-4.1')->pdf($file)->enhance()->tables()->summarize()->toMarkdown()
 */
class PDFBuilder
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
        private readonly SummarizerService $summarizerService,
        private readonly TableExtractionService $tableService,
        private readonly AskPDFService $askService,
        private readonly MarkdownService $markdownService,
        private readonly JSONConversionService $jsonService,
        ?string $language = null,
    ) {
        $this->startTime = microtime(true);
        $this->language = $language;
        $this->rawText = $this->pdfService->extractText($this->provider, $this->filePath, $this->language);
    }

    /**
     * Override language.
     */
    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Optional enhancement step (no-op at base level; extensible).
     */
    public function enhance(): static
    {
        return $this;
    }

    /**
     * Generate a summary of the PDF.
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
     * Extract all tables from the PDF text.
     */
    public function tables(): static
    {
        $this->tables = $this->tableService->extract($this->provider, $this->rawText);

        return $this;
    }

    /**
     * Convert the pipeline result to a markdown string.
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
     * Convert the PDF to a structured JSON object.
     *
     * @param  string|null  $prompt  Custom extraction prompt.
     * @return array<string, mixed>
     */
    public function toJson(?string $prompt = null): array
    {
        $this->jsonData = $this->jsonService->convert($this->provider, $this->rawText, $prompt);

        return $this->jsonData;
    }

    /**
     * Ask a question about the PDF contents (RAG-style).
     */
    public function ask(string $question): string
    {
        return $this->askService->ask($this->provider, $this->rawText, $question);
    }

    /**
     * Generate a structured extraction (alias for toJson with a structured focus).
     *
     * @return array<string, mixed>
     */
    public function structured(): array
    {
        return $this->toJson();
    }

    /**
     * Return the raw extracted PDF text.
     */
    public function text(): string
    {
        return $this->rawText;
    }

    /**
     * Return number of pages.
     */
    public function pages(): int
    {
        return $this->pdfService->pageCount($this->filePath);
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
            mimeType: 'application/pdf',
            filePath: $this->filePath,
            provider: $this->provider->getProviderName(),
            model: $this->provider->getCurrentModel(),
            processingTimeSeconds: microtime(true) - $this->startTime,
        );
    }

    // ------------------------------------------------------------------

    private function resolvedLanguage(): string
    {
        return $this->language ?? $this->languageDetector->detect($this->rawText);
    }
}
