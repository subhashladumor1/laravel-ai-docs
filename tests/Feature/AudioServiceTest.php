<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\AudioService;
use Subhashladumor1\LaravelAiDocs\Processors\AudioProcessor;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

describe('AudioService', function () {
    beforeEach(function () {
        $this->provider = new FakeAIProvider();
        $this->processor = new AudioProcessor([
            'enabled' => true,
            'supported_formats' => ['mp3', 'wav', 'm4a'],
            'max_file_size_mb' => 25,
        ]);
        $this->service = new AudioService($this->processor);
    });

    it('transcribes an audio file', function () {
        // Create a fake WAV-like file (just needs to exist and have content + .wav extension)
        $path = sys_get_temp_dir() . '/test_audio_' . uniqid() . '.wav';
        file_put_contents($path, str_repeat('0', 1024)); // 1KB fake audio

        $this->provider->withTranscriptionResponse('Hello, this is a test transcription.');

        $result = $this->service->transcribe($this->provider, $path);

        expect($result)->toBe('Hello, this is a test transcription.');

        unlink($path);
    });

    it('throws for non-existent audio file', function () {
        expect(fn() => $this->service->transcribe($this->provider, '/no/such/audio.mp3'))
            ->toThrow(FileProcessingException::class);
    });

    it('throws for unsupported audio format', function () {
        $path = sys_get_temp_dir() . '/test_audio_bad_' . uniqid() . '.ogg';
        file_put_contents($path, 'fake audio content');

        try {
            expect(fn() => $this->service->transcribe($this->provider, $path))
                ->toThrow(FileProcessingException::class, 'Unsupported audio format');
        } finally {
            unlink($path);
        }
    });

    it('throws when audio processing is disabled', function () {
        $disabledProcessor = new AudioProcessor(['enabled' => false]);
        $disabledService = new AudioService($disabledProcessor);

        $path = sys_get_temp_dir() . '/test_audio_disabled_' . uniqid() . '.mp3';
        file_put_contents($path, 'fake content');

        try {
            expect(fn() => $disabledService->transcribe($this->provider, $path))
                ->toThrow(FileProcessingException::class, 'disabled');
        } finally {
            unlink($path);
        }
    });

    it('passes language hint through to provider', function () {
        $path = sys_get_temp_dir() . '/test_audio_lang_' . uniqid() . '.mp3';
        file_put_contents($path, str_repeat('0', 512));

        $this->provider->withTranscriptionResponse('Transcription en français.');
        $result = $this->service->transcribe($this->provider, $path, 'fr');

        expect($result)->toBe('Transcription en français.');

        unlink($path);
    });
});
