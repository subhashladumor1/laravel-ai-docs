<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\SummarizerService;

describe('SummarizerService', function () {
    beforeEach(function () {
        $this->provider = new FakeAIProvider();
        $this->service = new SummarizerService();
    });

    it('summarizes text', function () {
        $this->provider->withTextResponse('This is the AI summary.');
        $result = $this->service->summarize($this->provider, 'Long document content here...');

        expect($result)->toBe('This is the AI summary.');
    });

    it('returns empty string for empty text', function () {
        $result = $this->service->summarize($this->provider, '');
        expect($result)->toBe('');
    });

    it('includes language instruction for non-English', function () {
        $this->provider->withTextResponse('Résumé en français.');
        $result = $this->service->summarize($this->provider, 'French document content.', 'fr');

        expect($result)->toBe('Résumé en français.');
    });

    it('accepts a custom prompt', function () {
        $this->provider->withTextResponse('Custom summary output.');
        $result = $this->service->summarize($this->provider, 'Some text.', null, 'Give me a one-line summary.');

        expect($result)->toBe('Custom summary output.');
    });

    it('handles very long text by truncating', function () {
        $longText = str_repeat('a', 20_000);
        $this->provider->withTextResponse('Truncated summary.');

        // Should not throw even with 20k chars
        $result = $this->service->summarize($this->provider, $longText);
        expect($result)->toBe('Truncated summary.');
    });
});
