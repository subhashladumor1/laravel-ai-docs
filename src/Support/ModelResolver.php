<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Support;

use Subhashladumor1\LaravelAiDocs\Exceptions\ProviderNotSupportedException;

/**
 * Resolves a model alias or short-hand string to a
 * { provider, model } tuple, optionally using a config map.
 */
final class ModelResolver
{
    /**
     * @param  array<string, array{provider: string, model: string}>  $aliases
     */
    public function __construct(
        private readonly array $aliases,
        private readonly string $defaultProvider,
    ) {
    }

    /**
     * Resolve a model identifier into a [provider, model] pair.
     *
     * @return array{provider: string, model: string}
     * @throws ProviderNotSupportedException
     */
    public function resolve(string $modelOrAlias): array
    {
        // Direct alias lookup (e.g. "gpt-4.1", "claude-3-5-sonnet")
        if (isset($this->aliases[$modelOrAlias])) {
            return $this->aliases[$modelOrAlias];
        }

        // provider:model format (e.g. "openai:gpt-4.1")
        if (str_contains($modelOrAlias, ':')) {
            [$provider, $model] = explode(':', $modelOrAlias, 2);

            return ['provider' => trim($provider), 'model' => trim($model)];
        }

        // Fall back: assume it is a raw model name for the default provider
        return ['provider' => $this->defaultProvider, 'model' => $modelOrAlias];
    }

    /**
     * Determine which provider to use given an explicit provider string
     * or fall back to the default.
     */
    public function resolveProvider(?string $provider): string
    {
        return $provider ?? $this->defaultProvider;
    }
}
