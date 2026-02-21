<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

class ClaudeProvider implements AIProviderInterface
{
    private readonly Client $http;

    private string $currentModel;

    private readonly string $visionModel;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
        $this->currentModel = $config['default_model'] ?? 'claude-3-5-sonnet-20241022';
        $this->visionModel = $config['vision_model'] ?? 'claude-3-5-sonnet-20241022';

        $this->http = new Client([
            'base_uri' => rtrim($config['base_uri'] ?? 'https://api.anthropic.com', '/') . '/',
            'timeout' => $config['timeout'] ?? 120,
            'headers' => [
                'x-api-key' => $config['api_key'] ?? '',
                'anthropic-version' => $config['api_version'] ?? '2023-06-01',
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
        $response = $this->messagesRequest(
            model: $model ?? $this->currentModel,
            messages: [['role' => 'user', 'content' => $prompt]],
        );

        return $response['content'][0]['text'] ?? '';
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
        $response = $this->messagesRequest(
            model: $model ?? $this->visionModel,
            messages: [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $imageData,
                            ],
                        ],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ],
            ],
        );

        return $response['content'][0]['text'] ?? '';
    }

    /**
     * {@inheritdoc}
     *
     * Claude does not natively support audio transcription.
     * This will throw an exception if called.
     */
    public function transcribeAudio(string $filePath, ?string $language = null): string
    {
        throw new FileProcessingException(
            'Claude provider does not support audio transcription. Use OpenAI provider instead.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generateStructured(string $prompt, ?string $model = null): array
    {
        $systemPrompt = 'You are a structured data extraction assistant. Always respond with valid JSON only. No markdown, no explanation, no code fences.';

        $response = $this->messagesRequest(
            model: $model ?? $this->currentModel,
            messages: [['role' => 'user', 'content' => $prompt]],
            system: $systemPrompt,
        );

        $content = $response['content'][0]['text'] ?? '{}';

        // Strip potential markdown code fences
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        return json_decode(trim($content), true) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'claude';
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
     * @param  array<int,array<string,mixed>>  $messages
     * @return array<string, mixed>
     */
    private function messagesRequest(
        string $model,
        array $messages,
        ?string $system = null,
    ): array {
        try {
            $body = [
                'model' => $model,
                'max_tokens' => 4096,
                'messages' => $messages,
            ];

            if ($system !== null) {
                $body['system'] = $system;
            }

            $response = $this->http->post('v1/messages', [
                'json' => $body,
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new FileProcessingException(
                "Claude API request failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
