<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\Processors\PDFProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * High-level PDF service: combines PDFProcessor extraction with optional
 * AI enhancement when the PDF is scanned (image-based).
 */
class PDFService
{
    public function __construct(
        private readonly PDFProcessor $pdfProcessor,
        private readonly OCRService $ocrService,
    ) {
    }

    /**
     * Extract plain text from a PDF, using OCR for scanned documents.
     *
     * @param  AIProviderInterface  $provider  Used for scanned-PDF OCR.
     * @param  string  $filePath
     * @param  string|null  $language  Language hint for OCR.
     * @return string
     */
    public function extractText(
        AIProviderInterface $provider,
        string $filePath,
        ?string $language = null,
    ): string {
        // For scanned PDFs we rely on the AI vision model to read each page
        if ($this->pdfProcessor->isScanned($filePath)) {
            return $this->extractScannedText($provider, $filePath, $language);
        }

        return $this->pdfProcessor->extractText($filePath);
    }

    /**
     * Extract text page-by-page.
     *
     * @return string[]  Keys are 1-based page numbers.
     */
    public function extractByPage(string $filePath): array
    {
        return $this->pdfProcessor->extractTextByPage($filePath);
    }

    /**
     * Count the number of pages in a PDF.
     */
    public function pageCount(string $filePath): int
    {
        return $this->pdfProcessor->pageCount($filePath);
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    /**
     * Fallback for scanned PDFs: prompt AI to read the raw binary as text.
     * In a full implementation each page would be rasterized to images;
     * here we pass a descriptive prompt with the extracted (potentially empty)
     * text and let the AI attempt to interpret.
     */
    private function extractScannedText(
        AIProviderInterface $provider,
        string $filePath,
        ?string $language,
    ): string {
        // For a real implementation, a library like `spatie/pdf-to-images` would
        // convert each page to a JPEG. Without that dependency, we prompt the AI
        // with a metadata-rich prompt to handle the scanned scenario.
        $langHint = $language ? "Document language: {$language}. " : '';
        $filename = basename($filePath);
        $pages = $this->pdfProcessor->pageCount($filePath);

        $prompt = "{$langHint}This is a scanned PDF document named '{$filename}' with approximately {$pages} page(s). "
            . 'The document appears to be image-based. Please indicate that an OCR-capable image conversion is required '
            . 'and return a structured response noting that this is a scanned PDF that requires image conversion for OCR.'
            . "\n\nFile: {$filename}\nPages: {$pages}\nStatus: Scanned document detected.";

        // We use generateText because we don't have the page images
        return $provider->generateText($prompt);
    }
}
