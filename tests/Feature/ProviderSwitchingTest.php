<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\AIDocsManager;
use Subhashladumor1\LaravelAiDocs\Exceptions\ProviderNotSupportedException;
use Subhashladumor1\LaravelAiDocs\Facades\AIDocs;
use Subhashladumor1\LaravelAiDocs\Providers\OpenAIProvider;
use Subhashladumor1\LaravelAiDocs\Providers\ClaudeProvider;
use Subhashladumor1\LaravelAiDocs\Providers\GeminiProvider;

describe('AIDocsManager - Provider Switching', function () {
    it('resolves to openai provider by default', function () {
        $manager = app(AIDocsManager::class);
        $provider = $manager->resolvedProvider();

        expect($provider)->toBeInstanceOf(OpenAIProvider::class);
    });

    it('switches to claude provider via ->provider()', function () {
        $manager = app(AIDocsManager::class)->provider('claude');
        $provider = $manager->resolvedProvider();

        expect($provider)->toBeInstanceOf(ClaudeProvider::class);
    });

    it('switches to gemini provider via ->provider()', function () {
        $manager = app(AIDocsManager::class)->provider('gemini');
        $provider = $manager->resolvedProvider();

        expect($provider)->toBeInstanceOf(GeminiProvider::class);
    });

    it('switches provider via ->model() alias', function () {
        $manager = app(AIDocsManager::class)->model('claude-3-5-sonnet');
        $provider = $manager->resolvedProvider();

        expect($provider)->toBeInstanceOf(ClaudeProvider::class);
    });

    it('switches provider via ->model() alias for gemini', function () {
        $manager = app(AIDocsManager::class)->model('gemini-1.5-pro');
        $provider = $manager->resolvedProvider();

        expect($provider)->toBeInstanceOf(GeminiProvider::class);
    });

    it('throws ProviderNotSupportedException for unknown provider', function () {
        $manager = app(AIDocsManager::class)->provider('unsupported-ai');

        expect(fn() => $manager->resolvedProvider())
            ->toThrow(ProviderNotSupportedException::class);
    });

    it('maintains immutability when switching providers', function () {
        $original = app(AIDocsManager::class);
        $switched = $original->provider('claude');

        expect($original->getActiveProvider())->toBe('openai')
            ->and($switched->getActiveProvider())->toBe('claude');
    });

    it('supports method-chaining language override', function () {
        $manager = app(AIDocsManager::class)->language('ar');

        // Ensure the manager is still an AIDocsManager
        expect($manager)->toBeInstanceOf(AIDocsManager::class);
    });
});
