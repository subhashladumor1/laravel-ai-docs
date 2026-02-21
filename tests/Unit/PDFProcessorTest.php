<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Processors\PDFProcessor;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

describe('PDFProcessor', function () {
    beforeEach(function () {
        $this->processor = new PDFProcessor([
            'scanned_detection' => true,
            'dpi' => 150,
        ]);
    });

    it('throws for a non-existent PDF file', function () {
        expect(fn() => $this->processor->extractText('/no/such/file.pdf'))
            ->toThrow(FileProcessingException::class);
    });

    it('throws for an invalid (non-PDF) file', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.pdf';
        file_put_contents($file, 'this is not a real pdf');

        try {
            expect(fn() => $this->processor->extractText($file))
                ->toThrow(FileProcessingException::class);
        } finally {
            unlink($file);
        }
    });

    it('returns 0 pages for an invalid PDF', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.pdf';
        file_put_contents($file, 'invalid');

        try {
            $count = $this->processor->pageCount($file);
            expect($count)->toBe(0);
        } finally {
            unlink($file);
        }
    });

    it('detects non-scanned PDF returns false for empty file', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.pdf';
        file_put_contents($file, 'invalid small content');

        try {
            // Small file + invalid = not treated as scanned
            $result = $this->processor->isScanned($file);
            expect($result)->toBe(false);
        } finally {
            unlink($file);
        }
    });
});
