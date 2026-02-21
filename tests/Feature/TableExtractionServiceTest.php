<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\TableExtractionService;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;

describe('TableExtractionService', function () {
    beforeEach(function () {
        $this->provider = new FakeAIProvider();
        $this->service = new TableExtractionService();
    });

    it('returns empty array for empty text', function () {
        $result = $this->service->extract($this->provider, '');
        expect($result)->toBeArray()->toBeEmpty();
    });

    it('extracts a single table from structured response', function () {
        $this->provider->withStructuredResponse([
            ['headers' => ['Item', 'Price'], 'rows' => [['Widget', '$10'], ['Gadget', '$20']]],
        ]);

        $result = $this->service->extract($this->provider, 'Item: Widget $10. Item: Gadget $20.');

        expect($result)->toBeArray()->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(TableDTO::class)
            ->and($result[0]->headers)->toBe(['Item', 'Price']);
    });

    it('extracts multiple tables', function () {
        $this->provider->withStructuredResponse([
            ['headers' => ['Name', 'Age'], 'rows' => [['Alice', '30']]],
            ['headers' => ['Product', 'Qty'], 'rows' => [['Widget', '5']]],
        ]);

        $result = $this->service->extract($this->provider, 'Some data with two tables.');

        expect($result)->toHaveCount(2)
            ->and($result[0]->headers)->toBe(['Name', 'Age'])
            ->and($result[1]->headers)->toBe(['Product', 'Qty']);
    });

    it('handles a tables-wrapped response', function () {
        $this->provider->withStructuredResponse([
            'tables' => [
                ['headers' => ['Col1'], 'rows' => [['Val1']]],
            ],
        ]);

        $result = $this->service->extract($this->provider, 'Data with table.');
        expect($result)->toHaveCount(1);
    });

    it('returns empty array when AI returns no tables', function () {
        $this->provider->withStructuredResponse([]);
        $result = $this->service->extract($this->provider, 'Text without any tables.');
        expect($result)->toBeEmpty();
    });
});
