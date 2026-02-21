<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;

/**
 * Converts a DocumentResultDTO (or raw text + tables) into clean Markdown.
 */
class MarkdownService
{
    /**
     * Convert a DocumentResultDTO to markdown.
     */
    public function fromResult(DocumentResultDTO $result): string
    {
        $parts = [];

        // Summary
        if ($result->summary !== null && trim($result->summary) !== '') {
            $parts[] = "## Summary\n\n{$result->summary}";
        }

        // Tables
        if (!empty($result->tables)) {
            $parts[] = $this->renderTables($result->tables);
        }

        // Full text
        if (trim($result->rawText) !== '') {
            $parts[] = "## Full Text\n\n{$result->rawText}";
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Convert raw text to lightweight markdown.
     */
    public function fromText(string $text): string
    {
        if (trim($text) === '') {
            return '';
        }

        // Replace multiple blank lines with at most two
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Convert tables to markdown format.
     *
     * @param  TableDTO[]  $tables
     */
    public function renderTables(array $tables): string
    {
        $parts = [];

        foreach ($tables as $i => $table) {
            $title = $table->title ?? 'Table ' . ($i + 1);
            $header = "## {$title}\n\n";
            $header .= $table->toMarkdown();
            $parts[] = $header;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Build a full markdown document from individual parts.
     *
     * @param  string  $title
     * @param  string  $summary
     * @param  TableDTO[]  $tables
     * @param  string  $rawText
     */
    public function build(
        string $title,
        string $summary = '',
        array $tables = [],
        string $rawText = '',
    ): string {
        $parts = [];

        if ($title !== '') {
            $parts[] = "# {$title}";
        }

        if ($summary !== '') {
            $parts[] = "## Summary\n\n{$summary}";
        }

        if (!empty($tables)) {
            $parts[] = $this->renderTables($tables);
        }

        if ($rawText !== '') {
            $parts[] = "## Content\n\n" . $this->fromText($rawText);
        }

        return implode("\n\n---\n\n", $parts);
    }
}
