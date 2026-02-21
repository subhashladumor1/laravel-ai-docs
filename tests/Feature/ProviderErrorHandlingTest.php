<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Providers\ClaudeProvider;
use Subhashladumor1\LaravelAiDocs\Providers\GeminiProvider;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

describe('Provider Error Handling', function () {
    it('fake provider throws on configured error', function () {
        $provider = (new FakeAIProvider())->shouldThrow('Service unavailable');

        expect(fn() => $provider->generateText('Any prompt'))
            ->toThrow(\RuntimeException::class, 'Service unavailable');
    });

    it('fake provider throws on vision when configured', function () {
        $provider = (new FakeAIProvider())->shouldThrow('Vision API error');

        expect(fn() => $provider->generateVision('prompt', base64_encode('img')))
            ->toThrow(\RuntimeException::class, 'Vision API error');
    });

    it('claude provider throws on audio transcription attempt', function () {
        $claude = new ClaudeProvider([
            'api_key' => 'test-key',
            'default_model' => 'claude-3-5-sonnet-20241022',
        ]);

        expect(fn() => $claude->transcribeAudio('/some/audio.mp3'))
            ->toThrow(FileProcessingException::class, 'Claude provider does not support audio');
    });

    it('gemini provider throws on audio transcription attempt', function () {
        $gemini = new GeminiProvider([
            'api_key' => 'test-key',
            'default_model' => 'gemini-1.5-pro',
        ]);

        expect(fn() => $gemini->transcribeAudio('/some/audio.mp3'))
            ->toThrow(FileProcessingException::class, 'Gemini provider does not support');
    });

    it('fake provider generates structured response', function () {
        $provider = (new FakeAIProvider())->withStructuredResponse([
            'title' => 'Test Document',
            'type' => 'invoice',
        ]);

        $result = $provider->generateStructured('Extract structure');

        expect($result)->toBe(['title' => 'Test Document', 'type' => 'invoice']);
    });

    it('setModel returns a new instance with updated model', function () {
        $provider = new FakeAIProvider();
        $updated = $provider->setModel('gpt-4.1');

        expect($updated)->not->toBe($provider)
            ->and($updated->getCurrentModel())->toBe('gpt-4.1');
    });
});
