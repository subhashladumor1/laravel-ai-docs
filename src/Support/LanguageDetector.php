<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Support;

/**
 * Heuristic language detector.
 *
 * In a real package you would call a language-detection library such as
 * `patrickschur/language-detection` or `pepijnolivier/laravel-language-detector`.
 * This implementation uses simple character-range heuristics and is designed
 * to work without additional dependencies while still being extensible.
 */
final class LanguageDetector
{
    public function __construct(
        private readonly string $defaultLanguage,
        private readonly bool $autoDetect,
    ) {
    }

    /**
     * Detect the language of a text string.
     *
     * Returns an ISO 639-1 code (e.g. "en", "ar", "zh").
     */
    public function detect(string $text): string
    {
        if (!$this->autoDetect || trim($text) === '') {
            return $this->defaultLanguage;
        }

        // Arabic Unicode block
        if (preg_match('/\p{Arabic}/u', $text)) {
            return 'ar';
        }

        // Japanese (Hiragana / Katakana) — check BEFORE Han since Japanese can mix Han
        if (preg_match('/\p{Hiragana}|\p{Katakana}/u', $text)) {
            return 'ja';
        }

        // Chinese (CJK Unified)
        if (preg_match('/\p{Han}/u', $text)) {
            return 'zh';
        }

        // Devanagari (Hindi, Sanskrit, etc.)
        if (preg_match('/\p{Devanagari}/u', $text)) {
            return 'hi';
        }

        // Cyrillic (Russian, etc.)
        if (preg_match('/\p{Cyrillic}/u', $text)) {
            return 'ru';
        }

        // Greek
        if (preg_match('/\p{Greek}/u', $text)) {
            return 'el';
        }

        // Korean (Hangul)
        if (preg_match('/[\x{AC00}-\x{D7AF}\x{1100}-\x{11FF}\x{3130}-\x{318F}]/u', $text)) {
            return 'ko';
        }

        // Default: English / Latin
        return $this->defaultLanguage;
    }

    /**
     * Return the configured default language code.
     */
    public function getDefault(): string
    {
        return $this->defaultLanguage;
    }
}
