<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;

describe('TableDTO', function () {
    it('constructs correctly', function () {
        $table = new TableDTO(
            headers: ['Name', 'Amount'],
            rows: [['Alice', '100'], ['Bob', '200']],
            pageNumber: 2,
            title: 'Payments',
        );

        expect($table->headers)->toBe(['Name', 'Amount'])
            ->and($table->rows)->toHaveCount(2)
            ->and($table->pageNumber)->toBe(2)
            ->and($table->title)->toBe('Payments');
    });

    it('converts to array', function () {
        $table = new TableDTO(['Col1', 'Col2'], [['A', 'B']]);
        $arr = $table->toArray();

        expect($arr)->toHaveKeys(['title', 'page_number', 'headers', 'rows']);
    });

    it('builds from array', function () {
        $raw = [
            'title' => 'Invoice Items',
            'page_number' => 1,
            'headers' => ['Item', 'Price'],
            'rows' => [['Widget', '9.99'], ['Gadget', '29.99']],
        ];

        $dto = TableDTO::fromArray($raw);

        expect($dto->title)->toBe('Invoice Items')
            ->and($dto->headers)->toBe(['Item', 'Price'])
            ->and($dto->rows)->toHaveCount(2);
    });

    it('generates markdown table', function () {
        $table = new TableDTO(
            headers: ['Name', 'Score'],
            rows: [['Alice', '95'], ['Bob', '87']],
        );
        $markdown = $table->toMarkdown();

        expect($markdown)->toContain('| Name | Score |')
            ->and($markdown)->toContain('| --- |')
            ->and($markdown)->toContain('| Alice | 95 |');
    });

    it('returns empty string for empty headers in markdown', function () {
        $table = new TableDTO([], []);
        expect($table->toMarkdown())->toBe('');
    });
});
