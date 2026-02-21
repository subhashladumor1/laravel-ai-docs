<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\AskPDFService;
use Subhashladumor1\LaravelAiDocs\Processors\TextChunker;

describe('AskPDFService', function () {
    beforeEach(function () {
        $this->provider = new FakeAIProvider();
        $this->chunker = new TextChunker(chunkSize: 500, overlap: 50);
        $this->service = new AskPDFService($this->chunker);
    });

    it('answers a question about a document', function () {
        $this->provider->withTextResponse('The total amount is $1,500.');

        $result = $this->service->ask(
            $this->provider,
            'Invoice #12345. Total: $1,500. Date: 2024-01-15.',
            'What is the total amount?',
        );

        expect($result)->toBe('The total amount is $1,500.');
    });

    it('handles empty document text', function () {
        $result = $this->service->ask($this->provider, '', 'Any question?');
        expect($result)->toContain('empty');
    });

    it('uses top-k relevant chunks', function () {
        $doc = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 200);
        $doc .= ' INVOICE TOTAL: $999.';

        $this->provider->withTextResponse('The invoice total is $999.');

        $result = $this->service->ask($this->provider, $doc, 'What is the invoice total?', topK: 3);

        expect($result)->toBe('The invoice total is $999.');
    });

    it('works with multi-page document', function () {
        $doc = '';
        for ($i = 1; $i <= 5; $i++) {
            $doc .= "\nPage {$i}: This page contains information about topic {$i}.\n";
        }
        $doc .= 'Contact email: test@example.com.';

        $this->provider->withTextResponse('The contact email is test@example.com.');

        $result = $this->service->ask($this->provider, $doc, 'What is the contact email?');

        expect($result)->toBe('The contact email is test@example.com.');
    });
});
