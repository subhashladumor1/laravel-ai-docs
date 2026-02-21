<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * Converts documents to a rich structured JSON object via AI.
 */
class JSONConversionService
{
    /**
     * Convert document text into a structured JSON representation.
     *
     * @param  AIProviderInterface  $provider
     * @param  string  $text  Raw text extracted from the document.
     * @param  string|null  $customPrompt  Override the default extraction prompt.
     * @return array<string, mixed>
     */
    public function convert(
        AIProviderInterface $provider,
        string $text,
        ?string $customPrompt = null,
    ): array {
        if (trim($text) === '') {
            return [];
        }

        $prompt = $customPrompt ?? <<<PROMPT
        Analyze the following document and extract a comprehensive structured JSON object.
        Include:
        - "title": document title if present
        - "date": any dates mentioned (ISO 8601 format where possible)
        - "author": author names if present
        - "sections": array of {heading, content} objects from document sections
        - "key_entities": named entities (people, organizations, locations, products)
        - "key_values": any key-value pairs or labeled data (e.g. invoice numbers, amounts, totals)
        - "summary": a brief (2-3 sentence) overview of the document
        - "document_type": inferred type (invoice, report, contract, letter, receipt, etc.)

        Return ONLY valid JSON. No explanation, no markdown code fences.

        Document:
        {$text}
        PROMPT;

        return $provider->generateStructured($prompt);
    }
}
