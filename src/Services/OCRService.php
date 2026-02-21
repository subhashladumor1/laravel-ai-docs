<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\Processors\ImageProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * Optical Character Recognition service.
 *
 * Sends an image (or a PDF page rendered as an image) to an
 * AI vision model and returns the recognized text.
 */
class OCRService
{
    public function __construct(
        private readonly ImageProcessor $imageProcessor,
    ) {
    }

    /**
     * Extract all text from an image file.
     *
     * @param  string  $filePath        Absolute path to the image.
     * @param  string|null  $language   ISO 639-1 language hint (e.g. "ar", "fr").
     * @param  string|null  $customPrompt  Override the default OCR prompt.
     */
    public function extractText(
        AIProviderInterface $provider,
        string $filePath,
        ?string $language = null,
        ?string $customPrompt = null,
    ): string {
        $processed = $this->imageProcessor->processForVision($filePath);

        $langHint = $language ? "The document language is {$language}. " : '';

        $prompt = $customPrompt
            ?? "{$langHint}Extract ALL text from this image exactly as written, preserving layout, line breaks, and structure. Return only the extracted text with no commentary.";

        return $provider->generateVision(
            prompt: $prompt,
            imageData: $processed['base64'],
            mimeType: $processed['mimeType'],
        );
    }
}
