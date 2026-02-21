<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\DTO;

/**
 * Represents a single row within an extracted table.
 *
 * @property-read string[]  $headers   Column headers.
 * @property-read string[][]  $rows    2-D grid of cell values.
 */
final class TableDTO
{
    /**
     * @param  string[]  $headers
     * @param  string[][]  $rows
     */
    public function __construct(
        public readonly array $headers,
        public readonly array $rows,
        public readonly int $pageNumber = 1,
        public readonly ?string $title = null,
    ) {
    }

    /**
     * Convert to a plain associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'page_number' => $this->pageNumber,
            'headers' => $this->headers,
            'rows' => $this->rows,
        ];
    }

    /**
     * Build from an AI-generated raw array.
     *
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            headers: array_map('strval', $raw['headers'] ?? []),
            rows: array_map(
                fn(array $row) => array_map('strval', $row),
                $raw['rows'] ?? []
            ),
            pageNumber: (int) ($raw['page_number'] ?? 1),
            title: isset($raw['title']) ? (string) $raw['title'] : null,
        );
    }

    /**
     * Convert to Markdown table string.
     */
    public function toMarkdown(): string
    {
        if (empty($this->headers)) {
            return '';
        }

        $lines = [];
        $header = '| ' . implode(' | ', $this->headers) . ' |';
        $divider = '| ' . implode(' | ', array_fill(0, count($this->headers), '---')) . ' |';

        $lines[] = $header;
        $lines[] = $divider;

        foreach ($this->rows as $row) {
            $lines[] = '| ' . implode(' | ', $row) . ' |';
        }

        return implode("\n", $lines);
    }
}
