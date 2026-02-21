<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs;

use Illuminate\Support\ServiceProvider;
use Subhashladumor1\LaravelAiDocs\AIDocsManager;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;
use Subhashladumor1\LaravelAiDocs\Providers\OpenAIProvider;
use Subhashladumor1\LaravelAiDocs\Providers\ClaudeProvider;
use Subhashladumor1\LaravelAiDocs\Providers\GeminiProvider;
use Subhashladumor1\LaravelAiDocs\Services\OCRService;
use Subhashladumor1\LaravelAiDocs\Services\PDFService;
use Subhashladumor1\LaravelAiDocs\Services\DocxService;
use Subhashladumor1\LaravelAiDocs\Services\AudioService;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;
use Subhashladumor1\LaravelAiDocs\Services\TableExtractionService;
use Subhashladumor1\LaravelAiDocs\Services\AskPDFService;
use Subhashladumor1\LaravelAiDocs\Services\MarkdownService;
use Subhashladumor1\LaravelAiDocs\Services\JSONConversionService;
use Subhashladumor1\LaravelAiDocs\Processors\ImageProcessor;
use Subhashladumor1\LaravelAiDocs\Processors\PDFProcessor;
use Subhashladumor1\LaravelAiDocs\Processors\AudioProcessor;
use Subhashladumor1\LaravelAiDocs\Processors\TextChunker;
use Subhashladumor1\LaravelAiDocs\Support\ModelResolver;
use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;

class LaravelAIDocsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/ai-docs.php',
            'ai-docs'
        );

        $this->registerSupportServices();
        $this->registerProcessors();
        $this->registerProviders();
        $this->registerCoreServices();
        $this->registerManager();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
        }
    }

    /**
     * Register support/utility services.
     */
    protected function registerSupportServices(): void
    {
        $this->app->singleton(ModelResolver::class, fn($app) => new ModelResolver(
            $app['config']->get('ai-docs.model_aliases', []),
            $app['config']->get('ai-docs.default_provider', 'openai'),
        ));

        $this->app->singleton(LanguageDetector::class, fn($app) => new LanguageDetector(
            $app['config']->get('ai-docs.language.default', 'en'),
            $app['config']->get('ai-docs.language.auto_detect', true),
        ));

        $this->app->singleton(FileValidator::class, fn($app) => new FileValidator(
            $app['config']->get('ai-docs.supported_formats', []),
        ));
    }

    /**
     * Register processors.
     */
    protected function registerProcessors(): void
    {
        $this->app->singleton(TextChunker::class, fn($app) => new TextChunker(
            $app['config']->get('ai-docs.rag.chunk_size', 1000),
            $app['config']->get('ai-docs.rag.chunk_overlap', 100),
        ));

        $this->app->singleton(ImageProcessor::class, fn($app) => new ImageProcessor(
            $app['config']->get('ai-docs.image', []),
        ));

        $this->app->singleton(PDFProcessor::class, fn($app) => new PDFProcessor(
            $app['config']->get('ai-docs.pdf', []),
        ));

        $this->app->singleton(AudioProcessor::class, fn($app) => new AudioProcessor(
            $app['config']->get('ai-docs.audio', []),
        ));
    }

    /**
     * Register AI providers.
     */
    protected function registerProviders(): void
    {
        $this->app->singleton(OpenAIProvider::class, fn($app) => new OpenAIProvider(
            $app['config']->get('ai-docs.providers.openai', []),
        ));

        $this->app->singleton(ClaudeProvider::class, fn($app) => new ClaudeProvider(
            $app['config']->get('ai-docs.providers.claude', []),
        ));

        $this->app->singleton(GeminiProvider::class, fn($app) => new GeminiProvider(
            $app['config']->get('ai-docs.providers.gemini', []),
        ));
    }

    /**
     * Register core document services.
     */
    protected function registerCoreServices(): void
    {
        $this->app->singleton(OCRService::class);
        $this->app->singleton(PDFService::class);
        $this->app->singleton(DocxService::class);
        $this->app->singleton(AudioService::class);
        $this->app->singleton(SummarizerService::class);
        $this->app->singleton(TableExtractionService::class);
        $this->app->singleton(AskPDFService::class);
        $this->app->singleton(MarkdownService::class);
        $this->app->singleton(JSONConversionService::class);
    }

    /**
     * Register the main AIDocsManager.
     */
    protected function registerManager(): void
    {
        $this->app->singleton(AIDocsManager::class, fn($app) => new AIDocsManager(
            $app['config']->get('ai-docs', []),
            $app[ModelResolver::class],
            $app[FileValidator::class],
            $app[LanguageDetector::class],
            $app[OpenAIProvider::class],
            $app[ClaudeProvider::class],
            $app[GeminiProvider::class],
            $app[ImageProcessor::class],
            $app[PDFProcessor::class],
            $app[AudioProcessor::class],
            $app[TextChunker::class],
            $app[PDFService::class],
            $app[DocxService::class],
            $app[AudioService::class],
            $app[OCRService::class],
            $app[SummarizerService::class],
            $app[TableExtractionService::class],
            $app[AskPDFService::class],
            $app[MarkdownService::class],
            $app[JSONConversionService::class],
        ));

        $this->app->alias(AIDocsManager::class, 'ai-docs');
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/config/ai-docs.php' => config_path('ai-docs.php'),
        ], 'ai-docs-config');
    }
}
