<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;
use Subhashladumor1\LaravelAiDocs\Support\FileValidator;

describe('File Validation Error Handling', function () {
    beforeEach(function () {
        $this->validator = new FileValidator([
            'document' => ['pdf', 'docx', 'txt'],
            'image' => ['jpg', 'jpeg', 'png'],
            'audio' => ['mp3', 'wav'],
        ]);
    });

    it('handles non-existent file gracefully', function () {
        expect(fn() => $this->validator->validate('/completely/invalid/path.pdf', 'document'))
            ->toThrow(FileProcessingException::class, 'does not exist');
    });

    it('handles empty file gracefully', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.pdf';
        file_put_contents($file, '');

        try {
            expect(fn() => $this->validator->validate($file, 'document'))
                ->toThrow(FileProcessingException::class, 'empty');
        } finally {
            unlink($file);
        }
    });

    it('handles unsupported file format', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.exe';
        file_put_contents($file, 'MZ Windows Executable');

        try {
            expect(fn() => $this->validator->validate($file, 'document'))
                ->toThrow(FileProcessingException::class, 'Unsupported');
        } finally {
            unlink($file);
        }
    });

    it('validates audio with supported format', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.mp3';
        file_put_contents($file, str_repeat('0', 100));

        try {
            $this->validator->validate($file, 'audio'); // Should not throw
            expect(true)->toBeTrue();
        } finally {
            unlink($file);
        }
    });

    it('throws for audio with unsupported format', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.flac';
        file_put_contents($file, str_repeat('0', 100));

        try {
            expect(fn() => $this->validator->validate($file, 'audio'))
                ->toThrow(FileProcessingException::class);
        } finally {
            unlink($file);
        }
    });
});
