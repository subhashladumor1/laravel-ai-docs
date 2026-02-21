<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\MarkdownService;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;
use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;

describe('MarkdownService', function () {
    beforeEach(function () {
        $this->service = new MarkdownService();
    });

    it('returns empty string for empty text', function () {
        expect($this->service->fromText(''))->toBe('');
    });

    it('converts plain text to markdown', function () {
        $result = $this->service->fromText("Line 1\n\nLine 2");
        expect($result)->toContain('Line 1');
    });

    it('renders table to markdown', function () {
        $table = new TableDTO(['Name', 'Price'], [['Widget', '$10']]);
        $result = $this->service->renderTables([$table]);

        expect($result)->toContain('| Name | Price |')
            ->and($result)->toContain('| Widget | $10 |');
    });

    it('builds full markdown document', function () {
        $table = new TableDTO(['A', 'B'], [['1', '2']]);
        $result = $this->service->build(
            title: 'Test Document',
            summary: 'This is a summary.',
            tables: [$table],
            rawText: 'Raw content here.',
        );

        expect($result)->toContain('# Test Document')
            ->and($result)->toContain('## Summary')
            ->and($result)->toContain('This is a summary.')
            ->and($result)->toContain('| A | B |')
            ->and($result)->toContain('## Content')
            ->and($result)->toContain('Raw content here.');
    });

    it('converts a DocumentResultDTO to markdown', function () {
        $dto = new DocumentResultDTO(
            rawText: 'Document body.',
            summary: 'Brief summary.',
            tables: [new TableDTO(['Col'], [['Val']])],
        );

        $result = $this->service->fromResult($dto);

        expect($result)->toContain('Brief summary.')
            ->and($result)->toContain('Document body.');
    });

    it('skips empty sections in build', function () {
        $result = $this->service->build(title: 'Title Only');
        expect($result)->toBe('# Title Only');
    });
});
