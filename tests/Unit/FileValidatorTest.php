<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Support\FileValidator;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

describe('FileValidator', function () {
    beforeEach(function () {
        $this->validator = new FileValidator([
            'document' => ['pdf', 'docx', 'txt'],
            'image' => ['jpg', 'jpeg', 'png'],
            'audio' => ['mp3', 'wav'],
        ]);
    });

    it('throws for non-existent file', function () {
        expect(fn() => $this->validator->validate('/non/existent/file.pdf', 'document'))
            ->toThrow(FileProcessingException::class, 'does not exist');
    });

    it('throws for unsupported file extension', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.xyz';
        file_put_contents($file, 'content');

        try {
            expect(fn() => $this->validator->validate($file, 'document'))
                ->toThrow(FileProcessingException::class, 'Unsupported file type');
        } finally {
            unlink($file);
        }
    });

    it('throws for empty file', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.pdf';
        file_put_contents($file, '');

        try {
            expect(fn() => $this->validator->validate($file, 'document'))
                ->toThrow(FileProcessingException::class, 'empty');
        } finally {
            unlink($file);
        }
    });

    it('passes validation for valid file', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($file, 'valid content');

        try {
            // Should not throw
            $this->validator->validate($file, 'document');
            expect(true)->toBeTrue();
        } finally {
            unlink($file);
        }
    });

    it('returns correct extension', function () {
        expect($this->validator->extension('/path/to/file.PDF'))->toBe('pdf');
    });

    it('returns mime type by extension', function () {
        expect($this->validator->mimeType('/path/to/file.pdf'))->toBe('application/pdf');
        expect($this->validator->mimeType('/path/to/file.jpg'))->toBe('image/jpeg');
        expect($this->validator->mimeType('/path/to/file.mp3'))->toBe('audio/mpeg');
    });
});
