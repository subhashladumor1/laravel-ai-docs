<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\ListItem;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

/**
 * Extracts text content from DOCX / DOC files via PhpWord.
 */
class DocxService
{
    /**
     * Extract all plain text from a DOCX file.
     *
     * @throws FileProcessingException
     */
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new FileProcessingException("DOCX file not found: {$filePath}");
        }

        try {
            $phpWord = IOFactory::load($filePath);
            $sections = $phpWord->getSections();
            $lines = [];

            foreach ($sections as $section) {
                foreach ($section->getElements() as $element) {
                    $text = $this->extractElementText($element);
                    if ($text !== '') {
                        $lines[] = $text;
                    }
                }
            }

            return implode("\n", $lines);
        } catch (\Exception $e) {
            throw new FileProcessingException(
                "DOCX extraction failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    /**
     * Recursively extract text from any PhpWord element.
     */
    private function extractElementText(mixed $element): string
    {
        // Plain text node
        if ($element instanceof Text) {
            return (string) $element->getText();
        }

        // Title element (heading)
        if ($element instanceof Title) {
            $text = $element->getText();
            return is_string($text) ? $text : '';
        }

        // List item
        if ($element instanceof ListItem) {
            return $this->extractElementText($element->getTextObject());
        }

        // Line break
        if ($element instanceof TextBreak) {
            return "\n";
        }

        // TextRun — a run of mixed inline elements
        if ($element instanceof TextRun) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $parts[] = $this->extractElementText($child);
            }
            return implode('', $parts);
        }

        // Table
        if ($element instanceof Table) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellText = [];
                    foreach ($cell->getElements() as $child) {
                        $cellText[] = $this->extractElementText($child);
                    }
                    $cells[] = trim(implode(' ', $cellText));
                }
                $rows[] = implode("\t", $cells);
            }
            return implode("\n", $rows);
        }

        // AbstractElement: try iterable children, then getText()
        if ($element instanceof AbstractElement) {
            if (method_exists($element, 'getElements')) {
                $parts = [];
                foreach ($element->getElements() as $child) {
                    $parts[] = $this->extractElementText($child);
                }
                $joined = implode('', $parts);
                if ($joined !== '') {
                    return $joined;
                }
            }

            if (method_exists($element, 'getText')) {
                return (string) $element->getText();
            }
        }

        return '';
    }
}
