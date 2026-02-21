<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * Generates AI summarizations of document content.
 */
class SummarizerService
{
    private const DEFAULT_MAX_CHARS = 12_000;

    /**
     * Summarize the provided text using an AI model.
     *
     * @param  string  $text           The full document text.
     * @param  string|null  $language  Language code for the summary instruction.
     * @param  string|null  $customPrompt  Custom prompt override.
     */
    public function summarize(
        AIProviderInterface $provider,
        string $text,
        ?string $language = null,
        ?string $customPrompt = null,
    ): string {
        if (trim($text) === '') {
            return '';
        }

        // Truncate if too long (avoid excessive token usage)
        $text = substr($text, 0, self::DEFAULT_MAX_CHARS);

        $langInstruction = $language && $language !== 'en'
            ? "Respond in language: {$language}. "
            : '';

        $prompt = $customPrompt ?? "{$langInstruction}Provide a concise, comprehensive summary of the following document. "
            . "Highlight the key points, main conclusions, and any critical details.\n\n"
            . "Document:\n{$text}";

        return $provider->generateText($prompt);
    }
}
