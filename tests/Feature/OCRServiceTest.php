<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\OCRService;
use Subhashladumor1\LaravelAiDocs\Processors\ImageProcessor;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

/**
 * We mock the ImageProcessor so OCRService tests do not depend on
 * GD / Intervention Image being fully configured in the test environment.
 */
describe('OCR - Image to Text', function () {
    beforeEach(function () {
        $this->provider = new FakeAIProvider();

        // Use Mockery to stub the ImageProcessor so we avoid real image processing
        $this->imageProcessor = Mockery::mock(ImageProcessor::class);
        $this->service = new OCRService($this->imageProcessor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('extracts text from a valid image', function () {
        $imagePath = sys_get_temp_dir() . '/test_ocr_' . uniqid() . '.png';
        file_put_contents($imagePath, 'fake-png-content');

        $this->imageProcessor
            ->shouldReceive('processForVision')
            ->with($imagePath)
            ->andReturn(['base64' => base64_encode('fake'), 'mimeType' => 'image/jpeg']);

        $this->provider->withVisionResponse('Extracted text from image.');

        $result = $this->service->extractText($this->provider, $imagePath);

        expect($result)->toBe('Extracted text from image.');

        unlink($imagePath);
    });

    it('throws for a non-existent image file', function () {
        $this->imageProcessor
            ->shouldReceive('processForVision')
            ->with('/no/such/image.jpg')
            ->andThrow(new FileProcessingException('Image file not found: /no/such/image.jpg'));

        expect(fn() => $this->service->extractText($this->provider, '/no/such/image.jpg'))
            ->toThrow(FileProcessingException::class);
    });

    it('accepts a custom prompt', function () {
        $imagePath = sys_get_temp_dir() . '/test_ocr_custom_' . uniqid() . '.png';
        file_put_contents($imagePath, 'fake-image');

        $this->imageProcessor
            ->shouldReceive('processForVision')
            ->with($imagePath)
            ->andReturn(['base64' => base64_encode('img'), 'mimeType' => 'image/jpeg']);

        $this->provider->withVisionResponse('Custom prompt result.');

        $result = $this->service->extractText($this->provider, $imagePath, 'en', 'Extract only numbers.');

        expect($result)->toBe('Custom prompt result.');

        unlink($imagePath);
    });

    it('passes language hint to prompt when provided', function () {
        $imagePath = sys_get_temp_dir() . '/test_ocr_lang_' . uniqid() . '.png';
        file_put_contents($imagePath, 'fake-image');

        $this->imageProcessor
            ->shouldReceive('processForVision')
            ->with($imagePath)
            ->andReturn(['base64' => base64_encode('img'), 'mimeType' => 'image/jpeg']);

        $this->provider->withVisionResponse('Arabic text extracted.');

        $result = $this->service->extractText($this->provider, $imagePath, 'ar');

        expect($result)->toBe('Arabic text extracted.');

        unlink($imagePath);
    });
});
