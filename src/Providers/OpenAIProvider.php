<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

class OpenAIProvider implements AIProviderInterface
{
    private readonly Client $http;

    private string $currentModel;

    private readonly string $visionModel;

    private readonly string $whisperModel;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
        $this->currentModel = $config['default_model'] ?? 'gpt-5.2';
        $this->visionModel = $config['vision_model'] ?? 'gpt-5.2';
        $this->whisperModel = $config['whisper_model'] ?? 'whisper-1';

        $apiKey = $config['api_key'] ?? '';
        if (empty($apiKey)) {
            throw new \Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $this->http = new Client([
            'base_uri' => rtrim($config['base_uri'] ?? 'https://api.openai.com/v1/', '/') . '/',
            'timeout' => $config['timeout'] ?? 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
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
        $response = $this->chatCompletion(
            model: $model ?? $this->currentModel,
            messages: [['role' => 'user', 'content' => $prompt]],
        );

        return $response['choices'][0]['message']['content'] ?? '';
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
        $response = $this->chatCompletion(
            model: $model ?? $this->visionModel,
            messages: [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}",
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
        );

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function transcribeAudio(string $filePath, ?string $language = null): string
    {
        if (!file_exists($filePath)) {
            throw new FileProcessingException("Audio file not found: {$filePath}");
        }

        try {
            $multipart = [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'rb'),
                    'filename' => basename($filePath),
                ],
                ['name' => 'model', 'contents' => $this->whisperModel],
                ['name' => 'response_format', 'contents' => 'verbose_json'],
            ];

            if ($language !== null) {
                $multipart[] = ['name' => 'language', 'contents' => $language];
            }

            $response = $this->http->post('audio/transcriptions', [
                'multipart' => $multipart,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            return $data['text'] ?? '';
        } catch (GuzzleException $e) {
            throw new FileProcessingException(
                "OpenAI audio transcription failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateStructured(string $prompt, ?string $model = null): array
    {
        $structured = $this->chatCompletion(
            model: $model ?? $this->currentModel,
            messages: [
                [
                    'role' => 'system',
                    'content' => 'You are a structured data extraction assistant. Always respond with valid JSON only. No markdown, no explanation.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            responseFormat: ['type' => 'json_object'],
        );

        $content = $structured['choices'][0]['message']['content'] ?? '{}';

        return json_decode($content, true) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'openai';
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
     * @param  array<string,string>|null  $responseFormat
     * @return array<string, mixed>
     */
    private function chatCompletion(
        string $model,
        array $messages,
        ?array $responseFormat = null,
    ): array {
        try {
            $body = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
            ];

            if ($responseFormat !== null) {
                $body['response_format'] = $responseFormat;
            }

            $response = $this->http->post('chat/completions', [
                'json' => $body,
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new FileProcessingException(
                "OpenAI API request failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
