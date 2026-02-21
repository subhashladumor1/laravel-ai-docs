<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Support\ModelResolver;
use Subhashladumor1\LaravelAiDocs\Exceptions\ProviderNotSupportedException;

describe('ModelResolver', function () {
    beforeEach(function () {
        $this->resolver = new ModelResolver(
            aliases: [
                'gpt-4.1' => ['provider' => 'openai', 'model' => 'gpt-4.1'],
                'gpt-4o' => ['provider' => 'openai', 'model' => 'gpt-4o'],
                'claude-3-5-sonnet' => ['provider' => 'claude', 'model' => 'claude-3-5-sonnet-20241022'],
                'gemini-1.5-pro' => ['provider' => 'gemini', 'model' => 'gemini-1.5-pro'],
            ],
            defaultProvider: 'openai',
        );
    });

    it('resolves a known alias', function () {
        $result = $this->resolver->resolve('gpt-4.1');
        expect($result)->toBe(['provider' => 'openai', 'model' => 'gpt-4.1']);
    });

    it('resolves claude alias', function () {
        $result = $this->resolver->resolve('claude-3-5-sonnet');
        expect($result['provider'])->toBe('claude');
    });

    it('resolves gemini alias', function () {
        $result = $this->resolver->resolve('gemini-1.5-pro');
        expect($result['provider'])->toBe('gemini');
    });

    it('resolves provider:model format', function () {
        $result = $this->resolver->resolve('openai:gpt-4-custom');
        expect($result)->toBe(['provider' => 'openai', 'model' => 'gpt-4-custom']);
    });

    it('falls back to default provider for unknown model', function () {
        $result = $this->resolver->resolve('some-unknown-model');
        expect($result['provider'])->toBe('openai');
        expect($result['model'])->toBe('some-unknown-model');
    });

    it('resolves explicit provider string', function () {
        expect($this->resolver->resolveProvider('claude'))->toBe('claude');
        expect($this->resolver->resolveProvider(null))->toBe('openai');
    });
});
