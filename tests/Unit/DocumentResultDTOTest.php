<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\DTO\DocumentResultDTO;
use Subhashladumor1\LaravelAiDocs\DTO\TableDTO;

describe('DocumentResultDTO', function () {
    it('constructs with minimal arguments (defaults)', function () {
        $dto = new DocumentResultDTO(rawText: 'Some content.');

        expect($dto->rawText)->toBe('Some content.')
            ->and($dto->summary)->toBeNull()
            ->and($dto->tables)->toBeArray()->toBeEmpty()
            ->and($dto->markdown)->toBeNull()
            ->and($dto->json)->toBeNull()
            ->and($dto->language)->toBeNull()
            ->and($dto->provider)->toBeNull()
            ->and($dto->model)->toBeNull()
            ->and($dto->processingTimeSeconds)->toBe(0.0);
    });

    it('constructs with full arguments', function () {
        $table = new TableDTO(['Col'], [['Val']]);
        $dto = new DocumentResultDTO(
            rawText: 'Body.',
            summary: 'Short summary.',
            tables: [$table],
            markdown: '# Title',
            json: ['key' => 'value'],
            language: 'fr',
            provider: 'claude',
            model: 'claude-3-5-sonnet',
            processingTimeSeconds: 1.23,
        );

        expect($dto->rawText)->toBe('Body.')
            ->and($dto->summary)->toBe('Short summary.')
            ->and($dto->tables)->toHaveCount(1)
            ->and($dto->markdown)->toBe('# Title')
            ->and($dto->json)->toBe(['key' => 'value'])
            ->and($dto->language)->toBe('fr')
            ->and($dto->provider)->toBe('claude')
            ->and($dto->model)->toBe('claude-3-5-sonnet')
            ->and($dto->processingTimeSeconds)->toBe(1.23);
    });

    it('converts to array with expected keys', function () {
        $dto = new DocumentResultDTO(rawText: 'Text body.');
        $arr = $dto->toArray();

        expect($arr)->toHaveKeys([
            'raw_text',
            'summary',
            'tables',
            'markdown',
            'json',
            'language',
            'provider',
            'model',
            'processing_time_seconds',
        ]);
    });

    it('toArray() serialises tables correctly', function () {
        $t1 = new TableDTO(['A', 'B'], [['1', '2']]);
        $dto = new DocumentResultDTO(rawText: '', tables: [$t1]);
        $arr = $dto->toArray();

        expect($arr['tables'])->toBeArray()->toHaveCount(1)
            ->and($arr['tables'][0])->toBeArray();
    });

    it('hasText() returns true when rawText is not empty', function () {
        $dto = new DocumentResultDTO(rawText: 'content');
        expect($dto->hasText())->toBeTrue();
    });

    it('hasText() returns false when rawText is empty', function () {
        $dto = new DocumentResultDTO(rawText: '');
        expect($dto->hasText())->toBeFalse();
    });

    it('hasTables() returns correct boolean', function () {
        $empty = new DocumentResultDTO(rawText: '');
        $withTabs = new DocumentResultDTO(rawText: '', tables: [new TableDTO(['A'], [])]);

        expect($empty->hasTables())->toBeFalse()
            ->and($withTabs->hasTables())->toBeTrue();
    });

    it('hasSummary() returns correct boolean', function () {
        $withSummary = new DocumentResultDTO(rawText: '', summary: 'A summary.');
        $withoutSummary = new DocumentResultDTO(rawText: '');

        expect($withSummary->hasSummary())->toBeTrue()
            ->and($withoutSummary->hasSummary())->toBeFalse();
    });

    it('hasJson() returns correct boolean', function () {
        $withJson = new DocumentResultDTO(rawText: '', json: ['key' => 'value']);
        $withoutJson = new DocumentResultDTO(rawText: '');

        expect($withJson->hasJson())->toBeTrue()
            ->and($withoutJson->hasJson())->toBeFalse();
    });

    it('with() returns a new immutable instance', function () {
        $original = new DocumentResultDTO(rawText: 'original');
        $modified = $original->with(['rawText' => 'modified']);

        expect($original->rawText)->toBe('original')
            ->and($modified->rawText)->toBe('modified');
    });

    it('toJson() returns empty array when json is null', function () {
        $dto = new DocumentResultDTO(rawText: '');
        expect($dto->toJson())->toBeArray()->toBeEmpty();
    });

    it('toJson() returns json array when set', function () {
        $dto = new DocumentResultDTO(rawText: '', json: ['title' => 'Invoice']);
        expect($dto->toJson())->toBe(['title' => 'Invoice']);
    });
});
