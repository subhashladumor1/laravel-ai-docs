<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * Extracts tabular data from document text using AI.
 * Returns an array of TableDTO objects.
 */
class TableExtractionService
{
    /**
     * Extract all tables found in the given document text.
     *
     * @return TableDTO[]
     */
    public function extract(
        AIProviderInterface $provider,
        string $text,
    ): array {
        if (trim($text) === '') {
            return [];
        }

        $prompt = <<<PROMPT
        You are a table extraction assistant.
        Analyze the following document text and extract ALL tables present.
        Return a JSON array of table objects. Each object must have:
        - "title": string (the table's title or null)
        - "page_number": integer (estimated page number or 1)
        - "headers": array of strings (column names)
        - "rows": array of arrays (each inner array has values matching the headers)

        If no tables are present, return an empty JSON array: []

        Document text:
        {$text}
        PROMPT;

        $raw = $provider->generateStructured($prompt);

        // The AI may return the array directly or wrapped in a key
        $tables = $raw['tables'] ?? $raw;

        if (!is_array($tables)) {
            return [];
        }

        // Normalize: if it is an indexed array of table objects
        if (!empty($tables) && isset($tables[0])) {
            return array_map(fn(array $t) => TableDTO::fromArray($t), $tables);
        }

        // If it is a single table object
        if (isset($tables['headers'])) {
            return [TableDTO::fromArray($tables)];
        }

        return [];
    }
}
