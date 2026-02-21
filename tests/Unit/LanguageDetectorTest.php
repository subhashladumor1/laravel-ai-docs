<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Support\LanguageDetector;

describe('LanguageDetector', function () {
    beforeEach(function () {
        $this->detector = new LanguageDetector('en', true);
    });

    it('returns default for empty text', function () {
        expect($this->detector->detect(''))->toBe('en');
    });

    it('detects English as default for Latin text', function () {
        expect($this->detector->detect('Hello world, how are you?'))->toBe('en');
    });

    it('detects Arabic text', function () {
        expect($this->detector->detect('مرحبا بالعالم'))->toBe('ar');
    });

    it('detects Chinese text', function () {
        expect($this->detector->detect('你好世界'))->toBe('zh');
    });

    it('detects Russian (Cyrillic) text', function () {
        expect($this->detector->detect('Привет мир'))->toBe('ru');
    });

    it('detects Japanese (Hiragana) text', function () {
        // Use pure Hiragana without Kanji (Kanji would trigger Chinese detection)
        expect($this->detector->detect('こんにちは、ありがとう'))->toBe('ja');
    });

    it('detects Korean (Hangul) text', function () {
        expect($this->detector->detect('안녕하세요'))->toBe('ko');
    });

    it('respects getDefault()', function () {
        $detector = new LanguageDetector('fr', true);
        expect($detector->getDefault())->toBe('fr');
    });

    it('returns default when auto-detect is disabled', function () {
        $detector = new LanguageDetector('en', false);
        expect($detector->detect('مرحبا بالعالم'))->toBe('en');
    });
});
