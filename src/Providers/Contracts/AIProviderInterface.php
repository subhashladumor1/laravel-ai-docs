<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Providers\Contracts;

/**
 * Contract that every AI provider must fulfil.
 */
interface AIProviderInterface
{
    /**
     * Generate plain text from a prompt.
     *
     * @param  string  $prompt   The full prompt to send.
     * @param  string|null  $model  Override the provider's default model.
     * @return string
     */
    public function generateText(string $prompt, ?string $model = null): string;

    /**
     * Generate text from a prompt that also includes an image (base64 or URL).
     *
     * @param  string  $prompt
     * @param  string  $imageData  Base-64 encoded image content.
     * @param  string  $mimeType   e.g. "image/jpeg"
     * @param  string|null  $model
     * @return string
     */
    public function generateVision(
        string $prompt,
        string $imageData,
        string $mimeType = 'image/jpeg',
        ?string $model = null
    ): string;

    /**
     * Transcribe audio from a local file path.
     *
     * @param  string  $filePath  Absolute path to the audio file.
     * @param  string|null  $language  ISO 639-1 language code hint.
     * @return string  The transcribed text.
     */
    public function transcribeAudio(string $filePath, ?string $language = null): string;

    /**
     * Generate a structured / JSON response.
     *
     * @param  string  $prompt
     * @param  string|null  $model
     * @return array<string, mixed>
     */
    public function generateStructured(string $prompt, ?string $model = null): array;

    /**
     * Return the provider slug (e.g. "openai", "claude", "gemini").
     */
    public function getProviderName(): string;

    /**
     * Return the model currently active for text generation.
     */
    public function getCurrentModel(): string;

    /**
     * Dynamically change the active model for the next call.
     */
    public function setModel(string $model): static;
}
