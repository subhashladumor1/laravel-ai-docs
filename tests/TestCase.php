<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Subhashladumor1\LaravelAiDocs\LaravelAIDocsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAIDocsServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'AIDocs' => \Subhashladumor1\LaravelAiDocs\Facades\AIDocs::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('ai-docs.default_provider', 'openai');
        $app['config']->set('ai-docs.providers.openai.api_key', 'test-openai-key');
        $app['config']->set('ai-docs.providers.claude.api_key', 'test-anthropic-key');
        $app['config']->set('ai-docs.providers.gemini.api_key', 'test-gemini-key');
        $app['config']->set('ai-docs.rag.chunk_size', 500);
        $app['config']->set('ai-docs.rag.chunk_overlap', 50);
        $app['config']->set('ai-docs.rag.top_k_chunks', 3);
    }

    /**
     * Create a temporary file with given content and return the path.
     */
    protected function createTempFile(string $content, string $extension = 'txt'): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ai_docs_test_') . '.' . $extension;
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Clean up a temporary file.
     */
    protected function deleteTempFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
