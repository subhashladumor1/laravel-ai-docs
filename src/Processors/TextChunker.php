<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Processors;

/**
 * Splits large text bodies into overlapping token-aware chunks
 * suitable for RAG (Retrieval-Augmented Generation) usage.
 *
 * Note: "Token" here is approximated as ~4 characters per token,
 * which is reasonable for GPT-style models.
 */
class TextChunker
{
    private const CHARS_PER_TOKEN = 4;

    public function __construct(
        private readonly int $chunkSize = 1000,
        private readonly int $overlap = 100,
    ) {
    }

    /**
     * Split $text into chunks of at most $chunkSize tokens,
     * with $overlap tokens of context carried forward.
     *
     * @return string[]
     */
    public function chunk(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $maxChars = $this->chunkSize * self::CHARS_PER_TOKEN;
        $overlapChars = $this->overlap * self::CHARS_PER_TOKEN;

        if (strlen($text) <= $maxChars) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $length = strlen($text);

        while ($start < $length) {
            $end = min($start + $maxChars, $length);
            $chunk = substr($text, $start, $end - $start);

            // Try to break at a sentence boundary
            if ($end < $length) {
                $lastBreak = $this->findSentenceBreak($chunk);
                if ($lastBreak !== false) {
                    $chunk = substr($chunk, 0, $lastBreak + 1);
                }
            }

            $chunks[] = trim($chunk);

            // Advance by chunk length minus overlap
            $advance = strlen($chunk) - $overlapChars;
            $start += max($advance, 1); // Prevent infinite loop on very short text
        }

        return array_values(array_filter($chunks));
    }

    /**
     * Score each chunk's relevance to a query using a simple TF-IDF–like
     * keyword matching approach (no external dependencies).
     *
     * @param  string[]  $chunks
     * @return string[]  Top $topK most relevant chunks, in relevance order.
     */
    public function retrieveRelevant(array $chunks, string $query, int $topK = 5): array
    {
        $keywords = $this->tokenize($query);

        if (empty($keywords) || empty($chunks)) {
            return array_slice($chunks, 0, $topK);
        }

        $scored = [];

        foreach ($chunks as $index => $chunk) {
            $chunkLower = strtolower($chunk);
            $score = 0;

            foreach ($keywords as $word) {
                $score += substr_count($chunkLower, $word);
            }

            $scored[] = ['score' => $score, 'index' => $index, 'chunk' => $chunk];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_column(array_slice($scored, 0, $topK), 'chunk');
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    /**
     * Find the last sentence-ending position (. ! ?) in a string.
     *
     * @return int|false
     */
    private function findSentenceBreak(string $text): int|false
    {
        $pos = false;

        foreach (['.', '!', '?', "\n\n"] as $delimiter) {
            $last = strrpos($text, $delimiter);
            if ($last !== false && ($pos === false || $last > $pos)) {
                $pos = $last;
            }
        }

        return $pos;
    }

    /**
     * Tokenize a query into lowercase keywords, removing stopwords.
     *
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        $stopwords = ['a', 'an', 'the', 'is', 'it', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'with', 'what', 'how', 'why'];

        $words = preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn($w) => !in_array($w, $stopwords, true) && strlen($w) > 2));
    }
}
