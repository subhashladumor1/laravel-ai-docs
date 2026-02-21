<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Processors\TextChunker;

describe('TextChunker', function () {
    beforeEach(function () {
        $this->chunker = new TextChunker(chunkSize: 50, overlap: 10);
    });

    it('returns empty array for empty text', function () {
        expect($this->chunker->chunk(''))->toBeArray()->toBeEmpty();
    });

    it('returns single chunk when text fits within chunk size', function () {
        $text = 'Short text.';
        $chunks = $this->chunker->chunk($text);

        expect($chunks)->toHaveCount(1)
            ->and($chunks[0])->toBe('Short text.');
    });

    it('splits long text into multiple chunks', function () {
        $text = str_repeat('Lorem ipsum dolor sit amet. ', 100);
        $chunks = $this->chunker->chunk($text);

        expect($chunks)->toBeArray()->not->toBeEmpty()
            ->and(count($chunks))->toBeGreaterThan(1);
    });

    it('retrieves relevant chunks based on keywords', function () {
        $chunks = [
            'Invoice number 12345 total amount 500.',
            'The weather is sunny today in Paris.',
            'Total due: $1,200. Payment method: credit card.',
            'Company name: ACME Corp. Address: 123 Main St.',
        ];

        $relevant = $this->chunker->retrieveRelevant($chunks, 'What is the total amount?', 2);

        expect($relevant)->toBeArray()->toHaveCount(2);

        // First result should contain 'total' keyword
        expect($relevant[0])->toContain('total');
    });

    it('returns all chunks when topK exceeds chunk count', function () {
        $chunks = ['A small chunk.', 'Another small chunk.'];
        $relevant = $this->chunker->retrieveRelevant($chunks, 'keyword', 10);

        expect($relevant)->toHaveCount(2);
    });
});
