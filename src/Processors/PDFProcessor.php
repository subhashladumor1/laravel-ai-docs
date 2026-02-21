<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Processors;

use Smalot\PdfParser\Parser;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

/**
 * Handles raw PDF text extraction and scanned-PDF detection.
 * Uses smalot/pdfparser for native PDF parsing.
 */
class PDFProcessor
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Extract plain text from a PDF file.
     *
     * @throws FileProcessingException
     */
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new FileProcessingException("PDF file not found: {$filePath}");
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            return trim($text);
        } catch (\Exception $e) {
            throw new FileProcessingException(
                "PDF text extraction failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Determine whether this PDF appears to be a scanned (image-only) document.
     *
     * A simple heuristic: if parsed text is shorter than 50 characters
     * but the file is larger than 100 KB it is likely scanned.
     */
    public function isScanned(string $filePath): bool
    {
        if (!($this->config['scanned_detection'] ?? true)) {
            return false;
        }

        try {
            $text = $this->extractText($filePath);
            $fileSize = filesize($filePath);

            return strlen(trim($text)) < 50 && $fileSize > 102_400;
        } catch (FileProcessingException) {
            return false;
        }
    }

    /**
     * Return the number of pages in a PDF.
     */
    public function pageCount(string $filePath): int
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);

            return count($pdf->getPages());
        } catch (\Exception) {
            return 0;
        }
    }

    /**
     * Extract text from each page individually.
     *
     * @return string[]  Indexed by page number (1-based).
     */
    public function extractTextByPage(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new FileProcessingException("PDF file not found: {$filePath}");
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = [];

            foreach ($pdf->getPages() as $i => $page) {
                $pages[$i + 1] = trim($page->getText());
            }

            return $pages;
        } catch (\Exception $e) {
            throw new FileProcessingException(
                "PDF page extraction failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
