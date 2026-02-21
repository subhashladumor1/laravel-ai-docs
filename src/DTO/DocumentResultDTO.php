<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\DTO;

/**
 * Wraps every possible output from a document processing pipeline.
 *
 * All fields are optional — use only the ones your pipeline produces.
 */
final class DocumentResultDTO
{
    /**
     * @param  string       $rawText                Plain-text extracted from the document.
     * @param  string|null  $summary                AI-generated summary.
     * @param  TableDTO[]   $tables                 Extracted tables.
     * @param  string|null  $markdown               Markdown-formatted representation.
     * @param  array<string,mixed>|null  $json       Structured JSON extraction result.
     * @param  string|null  $language               Detected or overridden ISO 639-1 language code.
     * @param  string|null  $mimeType               MIME type of the source file.
     * @param  string|null  $filePath               Absolute path to the source file.
     * @param  string|null  $provider               AI provider used (e.g. 'openai').
     * @param  string|null  $model                  AI model used (e.g. 'gpt-4.1').
     * @param  float        $processingTimeSeconds  Wall-clock seconds the pipeline took.
     * @param  string|null  $transcript             Raw audio transcript (AudioBuilder only).
     */
    public function __construct(
        public readonly string $rawText = '',
        public readonly ?string $summary = null,
        public readonly array $tables = [],
        public readonly ?string $markdown = null,
        public readonly ?array $json = null,
        public readonly ?string $language = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $filePath = null,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly float $processingTimeSeconds = 0.0,
        public readonly ?string $transcript = null,
    ) {
    }

    // ------------------------------------------------------------------
    //  Convenience helpers
    // ------------------------------------------------------------------

    /**
     * Whether any raw text was extracted from the document.
     */
    public function hasText(): bool
    {
        return trim($this->rawText) !== '';
    }

    /**
     * Whether at least one table was extracted.
     */
    public function hasTables(): bool
    {
        return !empty($this->tables);
    }

    /**
     * Whether an AI summary is available.
     */
    public function hasSummary(): bool
    {
        return $this->summary !== null && trim($this->summary) !== '';
    }

    /**
     * Whether a structured JSON extraction result is available.
     */
    public function hasJson(): bool
    {
        return $this->json !== null && !empty($this->json);
    }

    // ------------------------------------------------------------------
    //  Serialisation
    // ------------------------------------------------------------------

    /**
     * Convert to a plain associative array suitable for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'raw_text' => $this->rawText,
            'summary' => $this->summary,
            'tables' => array_map(fn(TableDTO $t) => $t->toArray(), $this->tables),
            'markdown' => $this->markdown,
            'json' => $this->json,
            'language' => $this->language,
            'mime_type' => $this->mimeType,
            'file_path' => $this->filePath,
            'provider' => $this->provider,
            'model' => $this->model,
            'processing_time_seconds' => $this->processingTimeSeconds,
            'transcript' => $this->transcript,
        ];
    }

    /**
     * Return the structured JSON result (alias for $this->json).
     *
     * @return array<string, mixed>
     */
    public function toJson(): array
    {
        return $this->json ?? [];
    }

    /**
     * Create a new instance with overridden properties (immutable wither).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function with(array $attributes): self
    {
        return new self(
            rawText: $attributes['rawText'] ?? $this->rawText,
            summary: $attributes['summary'] ?? $this->summary,
            tables: $attributes['tables'] ?? $this->tables,
            markdown: $attributes['markdown'] ?? $this->markdown,
            json: $attributes['json'] ?? $this->json,
            language: $attributes['language'] ?? $this->language,
            mimeType: $attributes['mimeType'] ?? $this->mimeType,
            filePath: $attributes['filePath'] ?? $this->filePath,
            provider: $attributes['provider'] ?? $this->provider,
            model: $attributes['model'] ?? $this->model,
            processingTimeSeconds: $attributes['processingTimeSeconds'] ?? $this->processingTimeSeconds,
            transcript: $attributes['transcript'] ?? $this->transcript,
        );
    }
}
