<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\JSONConversionService;

describe('JSONConversionService', function () {
    beforeEach(function () {
        $this->provider = new FakeAIProvider();
        $this->service = new JSONConversionService();
    });

    it('returns empty array for empty text', function () {
        $result = $this->service->convert($this->provider, '');
        expect($result)->toBeArray()->toBeEmpty();
    });

    it('converts document text to structured JSON', function () {
        $this->provider->withStructuredResponse([
            'title' => 'Test Invoice',
            'document_type' => 'invoice',
            'key_values' => ['total' => '$500'],
        ]);

        $result = $this->service->convert($this->provider, 'Invoice text...');

        expect($result)->toBeArray()
            ->and($result['title'])->toBe('Test Invoice')
            ->and($result['document_type'])->toBe('invoice');
    });

    it('accepts a custom prompt', function () {
        $this->provider->withStructuredResponse(['custom_field' => 'custom_value']);
        $result = $this->service->convert($this->provider, 'Some text', 'Extract only dates.');

        expect($result['custom_field'])->toBe('custom_value');
    });

    it('handles non-array structured response gracefully', function () {
        // Simulate provider returning empty array
        $this->provider->withStructuredResponse([]);
        $result = $this->service->convert($this->provider, 'Some text.');
        expect($result)->toBeArray()->toBeEmpty();
    });
});
