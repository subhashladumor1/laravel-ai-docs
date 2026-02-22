<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

class GeminiProvider implements AIProviderInterface
{
    private readonly Client $http;

    private string $currentModel;

    private readonly string $visionModel;

    private readonly string $apiKey;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
        $this->currentModel = $config['default_model'] ?? 'gemini-3.1-pro-preview';
        $this->visionModel = $config['vision_model'] ?? 'gemini-3.1-pro-preview';

        $this->apiKey = $config['api_key'] ?? '';
        if (empty($this->apiKey)) {
            throw new \Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        $this->http = new Client([
            'base_uri' => rtrim($config['base_uri'] ?? 'https://generativelanguage.googleapis.com/v1beta', '/') . '/',
            'timeout' => $config['timeout'] ?? 120,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function generateText(string $prompt, ?string $model = null): string
    {
        $activeModel = $model ?? $this->currentModel;
        $response = $this->generateContent(
            model: $activeModel,
            parts: [['text' => $prompt]],
        );

        return $this->extractText($response);
    }

    /**
     * {@inheritdoc}
     */
    public function generateVision(
        string $prompt,
        string $imageData,
        string $mimeType = 'image/jpeg',
        ?string $model = null
    ): string {
        $response = $this->generateContent(
            model: $model ?? $this->visionModel,
            parts: [
                ['text' => $prompt],
                [
                    'inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => $imageData,
                    ],
                ],
            ],
        );

        return $this->extractText($response);
    }

    /**
     * {@inheritdoc}
     *
     * Gemini does not natively support audio transcription via the REST API
     * in the same way as OpenAI Whisper. This implementation throws.
     */
    public function transcribeAudio(string $filePath, ?string $language = null): string
    {
        throw new FileProcessingException(
            'Gemini provider does not support direct audio transcription. Use OpenAI provider instead.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generateStructured(string $prompt, ?string $model = null): array
    {
        $fullPrompt = "{$prompt}\n\nRespond ONLY with valid JSON. No markdown, no explanation.";

        $response = $this->generateContent(
            model: $model ?? $this->currentModel,
            parts: [['text' => $fullPrompt]],
        );

        $content = $this->extractText($response);

        // Strip potential markdown fences
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        return json_decode(trim($content), true) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'gemini';
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentModel(): string
    {
        return $this->currentModel;
    }

    /**
     * {@inheritdoc}
     */
    public function setModel(string $model): static
    {
        $clone = clone $this;
        $clone->currentModel = $model;

        return $clone;
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    /**
     * @param  array<int,array<string,mixed>>  $parts
     * @return array<string, mixed>
     */
    private function generateContent(string $model, array $parts): array
    {
        try {
            $endpoint = "models/{$model}:generateContent?key={$this->apiKey}";

            $response = $this->http->post($endpoint, [
                'json' => [
                    'contents' => [
                        ['parts' => $parts],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                    ],
                ],
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new FileProcessingException(
                "Gemini API request failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractText(array $response): string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
