<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Tests\Fakes;

use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * A fully in-memory fake AI provider for testing.
 * All responses can be configured before the test runs.
 */
class FakeAIProvider implements AIProviderInterface
{
    private string $providerName = 'fake';

    private string $currentModel = 'fake-model';

    private string $textResponse = 'Fake AI text response.';

    private string $visionResponse = 'Fake OCR extracted text.';

    private string $transcriptionResponse = 'Fake audio transcription.';

    /** @var array<string, mixed> */
    private array $structuredResponse = [];

    private bool $shouldThrow = false;

    private string $throwMessage = 'Fake provider error.';

    // ------------------------------------------------------------------
    //  Configure the fake
    // ------------------------------------------------------------------

    public function withTextResponse(string $response): static
    {
        $this->textResponse = $response;

        return $this;
    }

    public function withVisionResponse(string $response): static
    {
        $this->visionResponse = $response;

        return $this;
    }

    public function withTranscriptionResponse(string $response): static
    {
        $this->transcriptionResponse = $response;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function withStructuredResponse(array $response): static
    {
        $this->structuredResponse = $response;

        return $this;
    }

    public function shouldThrow(string $message = 'Fake provider error.'): static
    {
        $this->shouldThrow = true;
        $this->throwMessage = $message;

        return $this;
    }

    // ------------------------------------------------------------------
    //  AIProviderInterface implementation
    // ------------------------------------------------------------------

    public function generateText(string $prompt, ?string $model = null): string
    {
        if ($this->shouldThrow) {
            throw new \RuntimeException($this->throwMessage);
        }

        return $this->textResponse;
    }

    public function generateVision(
        string $prompt,
        string $imageData,
        string $mimeType = 'image/jpeg',
        ?string $model = null,
    ): string {
        if ($this->shouldThrow) {
            throw new \RuntimeException($this->throwMessage);
        }

        return $this->visionResponse;
    }

    public function transcribeAudio(string $filePath, ?string $language = null): string
    {
        if ($this->shouldThrow) {
            throw new \RuntimeException($this->throwMessage);
        }

        return $this->transcriptionResponse;
    }

    public function generateStructured(string $prompt, ?string $model = null): array
    {
        if ($this->shouldThrow) {
            throw new \RuntimeException($this->throwMessage);
        }

        return $this->structuredResponse;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getCurrentModel(): string
    {
        return $this->currentModel;
    }

    public function setModel(string $model): static
    {
        $clone = clone $this;
        $clone->currentModel = $model;

        return $clone;
    }
}
