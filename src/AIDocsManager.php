<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs;

use Subhashladumor1\LaravelAiDocs\Builders\AudioBuilder;
use Subhashladumor1\LaravelAiDocs\Builders\DocumentBuilder;
use Subhashladumor1\LaravelAiDocs\Builders\ImageBuilder;
use Subhashladumor1\LaravelAiDocs\Builders\PDFBuilder;
use Subhashladumor1\LaravelAiDocs\Exceptions\ProviderNotSupportedException;
use Subhashladumor1\LaravelAiDocs\Processors\AudioProcessor;
use Subhashladumor1\LaravelAiDocs\Processors\ImageProcessor;
use Subhashladumor1\LaravelAiDocs\Processors\PDFProcessor;
use Subhashladumor1\LaravelAiDocs\Processors\TextChunker;
use Subhashladumor1\LaravelAiDocs\Providers\ClaudeProvider;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;
use Subhashladumor1\LaravelAiDocs\Providers\GeminiProvider;
use Subhashladumor1\LaravelAiDocs\Providers\OpenAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\AskPDFService;
use Subhashladumor1\LaravelAiDocs\Services\AudioService;
use Subhashladumor1\LaravelAiDocs\Services\DocxService;
use Subhashladumor1\LaravelAiDocs\Services\JSONConversionService;
use Subhashladumor1\LaravelAiDocs\Services\MarkdownService;
use Subhashladumor1\LaravelAiDocs\Services\OCRService;
use Subhashladumor1\LaravelAiDocs\Services\PDFService;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;
use Subhashladumor1\LaravelAiDocs\Services\TableExtractionService;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;
use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;
use Subhashladumor1\LaravelAiDocs\Support\ModelResolver;

/**
 * Central orchestrator and entry-point for the LaravelAIDocs package.
 *
 * Provides the fluent API:
 *   AIDocs::model('gpt-4.1')->pdf($file)->summarize()->toMarkdown()
 *   AIDocs::image($file)->text()
 *   AIDocs::audio($file)->transcribe()
 */
class AIDocsManager
{
    /** Currently active provider name */
    private string $activeProvider;

    /** Currently active model (overrides provider default) */
    private ?string $activeModel = null;

    /** Override language for all operations */
    private ?string $language = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly ModelResolver $modelResolver,
        private readonly FileValidator $fileValidator,
        private readonly LanguageDetector $languageDetector,
        private readonly OpenAIProvider $openAIProvider,
        private readonly ClaudeProvider $claudeProvider,
        private readonly GeminiProvider $geminiProvider,
        private readonly ImageProcessor $imageProcessor,
        private readonly PDFProcessor $pdfProcessor,
        private readonly AudioProcessor $audioProcessor,
        private readonly TextChunker $textChunker,
        private readonly PDFService $pdfService,
        private readonly DocxService $docxService,
        private readonly AudioService $audioService,
        private readonly OCRService $ocrService,
        private readonly SummarizerService $summarizerService,
        private readonly TableExtractionService $tableService,
        private readonly AskPDFService $askService,
        private readonly MarkdownService $markdownService,
        private readonly JSONConversionService $jsonService,
    ) {
        $this->activeProvider = $config['default_provider'] ?? 'openai';
    }

    // ------------------------------------------------------------------
    //  Fluent Configuration
    // ------------------------------------------------------------------

    /**
     * Switch the active AI model (and auto-detect provider if aliased).
     *
     * @example AIDocs::model('claude-3-5-sonnet')->pdf($file)->summarize()
     */
    public function model(string $model): static
    {
        $resolved = $this->modelResolver->resolve($model);

        $clone = clone $this;
        $clone->activeProvider = $resolved['provider'];
        $clone->activeModel = $resolved['model'];

        return $clone;
    }

    /**
     * Explicitly switch provider without changing model.
     *
     * @example AIDocs::provider('gemini')->pdf($file)->toJson()
     */
    public function provider(string $provider): static
    {
        $clone = clone $this;
        $clone->activeProvider = strtolower($provider);
        $clone->activeModel = null;

        return $clone;
    }

    /**
     * Override the processing language.
     *
     * @example AIDocs::language('ar')->pdf($file)->summarize()
     */
    public function language(string $language): static
    {
        $clone = clone $this;
        $clone->language = $language;

        return $clone;
    }

    // ------------------------------------------------------------------
    //  Entry Points (Builders)
    // ------------------------------------------------------------------

    /**
     * Begin a PDF processing pipeline.
     *
     * @example AIDocs::pdf($file)->summarize()->toMarkdown()
     */
    public function pdf(string $filePath): PDFBuilder
    {
        $this->fileValidator->validate($filePath, 'document');

        return new PDFBuilder(
            filePath: $filePath,
            provider: $this->resolvedProvider(),
            fileValidator: $this->fileValidator,
            languageDetector: $this->languageDetector,
            pdfService: $this->pdfService,
            summarizerService: $this->summarizerService,
            tableService: $this->tableService,
            askService: $this->askService,
            markdownService: $this->markdownService,
            jsonService: $this->jsonService,
        );
    }

    /**
     * Begin an image processing pipeline.
     *
     * @example AIDocs::image($file)->text()
     */
    public function image(string $filePath): ImageBuilder
    {
        $this->fileValidator->validate($filePath, 'image');

        return new ImageBuilder(
            filePath: $filePath,
            provider: $this->resolvedProvider(),
            fileValidator: $this->fileValidator,
            languageDetector: $this->languageDetector,
            imageProcessor: $this->imageProcessor,
            ocrService: $this->ocrService,
            summarizerService: $this->summarizerService,
            tableService: $this->tableService,
            askService: $this->askService,
            markdownService: $this->markdownService,
            jsonService: $this->jsonService,
        );
    }

    /**
     * Begin an audio processing pipeline.
     *
     * @example AIDocs::audio($file)->transcribe()
     */
    public function audio(string $filePath): AudioBuilder
    {
        $this->fileValidator->validate($filePath, 'audio');

        return new AudioBuilder(
            filePath: $filePath,
            provider: $this->resolvedProvider(),
            fileValidator: $this->fileValidator,
            languageDetector: $this->languageDetector,
            audioService: $this->audioService,
            summarizerService: $this->summarizerService,
        );
    }

    /**
     * Begin a generic document processing pipeline (DOCX, TXT, etc.).
     *
     * @example AIDocs::document($file)->summarize()->toMarkdown()
     */
    public function document(string $filePath): DocumentBuilder
    {
        $this->fileValidator->validate($filePath, 'document');

        return new DocumentBuilder(
            filePath: $filePath,
            provider: $this->resolvedProvider(),
            fileValidator: $this->fileValidator,
            languageDetector: $this->languageDetector,
            pdfService: $this->pdfService,
            docxService: $this->docxService,
            ocrService: $this->ocrService,
            summarizerService: $this->summarizerService,
            tableService: $this->tableService,
            askService: $this->askService,
            markdownService: $this->markdownService,
            jsonService: $this->jsonService,
        );
    }

    // ------------------------------------------------------------------
    //  Provider Resolution
    // ------------------------------------------------------------------

    /**
     * Resolve the active AIProviderInterface implementation,
     * optionally with an overridden model.
     *
     * @throws ProviderNotSupportedException
     */
    public function resolvedProvider(): AIProviderInterface
    {
        $provider = match ($this->activeProvider) {
            'openai' => $this->openAIProvider,
            'claude' => $this->claudeProvider,
            'gemini' => $this->geminiProvider,
            default => throw new ProviderNotSupportedException(
                "Provider '{$this->activeProvider}' is not supported. "
                . 'Supported providers: openai, claude, gemini.'
            ),
        };

        if ($this->activeModel !== null) {
            $provider = $provider->setModel($this->activeModel);
        }

        return $provider;
    }

    /**
     * Return the currently active provider name.
     */
    public function getActiveProvider(): string
    {
        return $this->activeProvider;
    }

    /**
     * Return the currently active model override (null = provider default).
     */
    public function getActiveModel(): ?string
    {
        return $this->activeModel;
    }
}
