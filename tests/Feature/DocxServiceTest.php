<?php

declare(strict_types=1);

use Subhashladumor1\LaravelAiDocs\Tests\Fakes\FakeAIProvider;
use Subhashladumor1\LaravelAiDocs\Services\DocxService;
use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

describe('DocxService', function () {
    beforeEach(function () {
        $this->service = new DocxService();
    });

    it('throws for a non-existent DOCX file', function () {
        expect(fn() => $this->service->extractText('/no/such/file.docx'))
            ->toThrow(FileProcessingException::class, 'not found');
    });

    it('throws for an invalid (non-DOCX) file', function () {
        $file = tempnam(sys_get_temp_dir(), 'test') . '.docx';
        file_put_contents($file, 'this is not a real docx');

        try {
            expect(fn() => $this->service->extractText($file))
                ->toThrow(FileProcessingException::class, 'extraction failed');
        } finally {
            unlink($file);
        }
    });

    it('extracts text from a valid DOCX file using PhpWord', function () {
        // Create a real DOCX in memory using PhpWord
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Hello from DOCX.');
        $section->addText('Second line.');

        $path = sys_get_temp_dir() . '/test_docx_' . uniqid() . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        try {
            $text = $this->service->extractText($path);
            expect($text)->toContain('Hello from DOCX.')
                ->and($text)->toContain('Second line.');
        } finally {
            unlink($path);
        }
    });

    it('extracts text from a DOCX with a table', function () {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('Cell A1');
        $row->addCell()->addText('Cell B1');

        $path = sys_get_temp_dir() . '/test_docx_table_' . uniqid() . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        try {
            $text = $this->service->extractText($path);
            expect($text)->toContain('Cell A1')
                ->and($text)->toContain('Cell B1');
        } finally {
            unlink($path);
        }
    });
});
