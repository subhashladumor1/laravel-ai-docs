<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Facades;

use Illuminate\Support\Facades\Facade;
use Subhashladumor1\LaravelAiDocs\AIDocsManager;
use Subhashladumor1\LaravelAiDocs\Builders\DocumentBuilder;
use Subhashladumor1\LaravelAiDocs\Builders\ImageBuilder;
use Subhashladumor1\LaravelAiDocs\Builders\PDFBuilder;
use Subhashladumor1\LaravelAiDocs\Builders\AudioBuilder;

/**
 * @method static AIDocsManager model(string $model)
 * @method static AIDocsManager provider(string $provider)
 * @method static AIDocsManager language(string $language)
 * @method static PDFBuilder pdf(string $filePath)
 * @method static ImageBuilder image(string $filePath)
 * @method static AudioBuilder audio(string $filePath)
 * @method static DocumentBuilder document(string $filePath)
 *
 * @see \Subhashladumor1\LaravelAiDocs\AIDocsManager
 */
class AIDocs extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ai-docs';
    }
}
