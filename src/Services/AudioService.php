<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\Processors\AudioProcessor;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * Coordinates audio preparation and AI-powered transcription.
 */
class AudioService
{
    public function __construct(
        private readonly AudioProcessor $audioProcessor,
    ) {
    }

    /**
     * Transcribe an audio file to text.
     *
     * @param  AIProviderInterface  $provider   Must support transcribeAudio().
     * @param  string  $filePath                Absolute path to the audio file.
     * @param  string|null  $language           ISO 639-1 language hint.
     * @return string  Transcription text.
     */
    public function transcribe(
        AIProviderInterface $provider,
        string $filePath,
        ?string $language = null,
    ): string {
        $preparedPath = $this->audioProcessor->prepare($filePath);

        return $provider->transcribeAudio($preparedPath, $language);
    }
}
