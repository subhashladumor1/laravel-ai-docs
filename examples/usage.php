<?php

declare(strict_types=1);

/**
 * Laravel AI Docs - Example Usage
 *
 * This file demonstrates all major features of the laravel-ai-docs package.
 * Copy individual snippets into your Laravel application.
 *
 * Prerequisites:
 *   composer require subhashladumor1/laravel-ai-docs
 *   php artisan vendor:publish --tag=ai-docs-config
 *   Set your API keys in .env
 */

use Subhashladumor1\LaravelAiDocs\Facades\AIDocs;

// ==========================================================
// 1. Basic PDF Text Extraction
// ==========================================================

$text = AIDocs::pdf(storage_path('app/documents/report.pdf'))->text();

echo "Extracted text: " . substr($text, 0, 200) . "...\n";

// ==========================================================
// 2. Full PDF Pipeline (model → enhance → tables → summarize → markdown)
// ==========================================================

$markdown = AIDocs::model('gpt-4.1')
    ->pdf(storage_path('app/documents/financial-report.pdf'))
    ->enhance()     // optional enhancement step
    ->tables()      // extract all data tables
    ->summarize()   // generate an executive summary
    ->toMarkdown(); // convert result to markdown

file_put_contents(storage_path('app/output/report.md'), $markdown);

// ==========================================================
// 3. Image OCR – Extract text from an image
// ==========================================================

$textFromImage = AIDocs::image(storage_path('app/uploads/receipt.jpg'))->text();
echo "OCR Result: {$textFromImage}\n";

// ==========================================================
// 4. Image OCR with language hint (Arabic, Chinese, etc.)
// ==========================================================

$arabicText = AIDocs::image(storage_path('app/uploads/arabic-invoice.jpg'))
    ->language('ar')
    ->text();

echo "Arabic OCR: {$arabicText}\n";

// ==========================================================
// 5. Audio Transcription (Whisper-compatible via OpenAI)
// ==========================================================

$transcript = AIDocs::audio(storage_path('app/audio/meeting.mp3'))->transcribe();
echo "Transcript: {$transcript}\n";

// ==========================================================
// 6. Audio → Transcribe → Summarize
// ==========================================================

$meetingSummary = AIDocs::audio(storage_path('app/audio/team-standup.m4a'))
    ->language('en')
    ->summarize();

echo "Meeting Summary: {$meetingSummary}\n";

// ==========================================================
// 7. Ask PDF – RAG-style question answering
// ==========================================================

$answer = AIDocs::pdf(storage_path('app/uploads/contract.pdf'))
    ->ask('What is the payment due date?');

echo "Answer: {$answer}\n";

// Invoice specific:
$total = AIDocs::pdf(storage_path('app/uploads/invoice.pdf'))
    ->ask('What is the total amount due including VAT?');

echo "Total: {$total}\n";

// ==========================================================
// 8. PDF to Structured JSON
// ==========================================================

$json = AIDocs::pdf(storage_path('app/uploads/invoice.pdf'))->toJson();

// Result:
// [
//   'title'         => 'Invoice #12345',
//   'document_type' => 'invoice',
//   'date'          => '2024-01-15',
//   'key_values'    => ['total' => '$1,500.00', 'tax' => '$150.00'],
//   'key_entities'  => ['Client Corp', 'Vendor Ltd'],
//   'summary'       => 'An invoice for professional services rendered...',
// ]

echo "Document type: " . ($json['document_type'] ?? 'unknown') . "\n";

// ==========================================================
// 9. Structured extraction with custom prompt
// ==========================================================

$structured = AIDocs::pdf(storage_path('app/uploads/invoice.pdf'))->toJson(
    'Extract only: invoice_number, total_amount, due_date, vendor_name. Return as JSON.'
);

echo "Invoice #" . ($structured['invoice_number'] ?? 'N/A') . "\n";

// ==========================================================
// 10. Table Extraction from PDF
// ==========================================================

$builder = AIDocs::pdf(storage_path('app/uploads/financial-report.pdf'))->tables();
$result = $builder->result();

foreach ($result->tables as $index => $table) {
    echo "\nTable {$index}: " . ($table->title ?? 'Untitled') . "\n";
    echo $table->toMarkdown() . "\n";
}

// ==========================================================
// 11. DOCX Processing
// ==========================================================

$docText = AIDocs::document(storage_path('app/uploads/proposal.docx'))->text();
$docSummary = AIDocs::document(storage_path('app/uploads/proposal.docx'))
    ->summarize()
    ->text();
$docMarkdown = AIDocs::document(storage_path('app/uploads/proposal.docx'))
    ->enhance()
    ->summarize()
    ->tables()
    ->toMarkdown();

// ==========================================================
// 12. Multi-Provider Examples
// ==========================================================

// OpenAI GPT-4.1
$openaiResult = AIDocs::model('gpt-4.1')
    ->pdf(storage_path('app/documents/report.pdf'))
    ->summarize()
    ->result();

// Anthropic Claude 3.5 Sonnet
$claudeResult = AIDocs::model('claude-3-5-sonnet')
    ->pdf(storage_path('app/documents/report.pdf'))
    ->toJson();

// Google Gemini 1.5 Pro
$geminiResult = AIDocs::model('gemini-1.5-pro')
    ->pdf(storage_path('app/documents/report.pdf'))
    ->ask('What are the key recommendations?');

// Explicit provider:
$claudeAnswer = AIDocs::provider('claude')
    ->pdf(storage_path('app/documents/contract.pdf'))
    ->ask('What are the termination conditions?');

// ==========================================================
// 13. Getting the full DocumentResultDTO
// ==========================================================

$dto = AIDocs::model('gpt-4.1')
    ->pdf(storage_path('app/documents/report.pdf'))
    ->enhance()
    ->tables()
    ->summarize()
    ->result();

// Access all properties:
echo "Raw text length: " . strlen($dto->rawText) . " chars\n";
echo "Summary: " . $dto->summary . "\n";
echo "Tables found: " . count($dto->tables) . "\n";
echo "Language: " . $dto->language . "\n";
echo "Provider: " . $dto->provider . "\n";
echo "Model: " . $dto->model . "\n";
echo "Processing time: " . round($dto->processingTimeSeconds, 2) . "s\n";

// Convert to array for storage/API response:
$array = $dto->toArray();
// Or JSON:
$json = json_encode($dto->toArray(), JSON_PRETTY_PRINT);

// ==========================================================
// 14. Language Override for Multilingual Documents
// ==========================================================

$frenchSummary = AIDocs::pdf(storage_path('app/documents/rapport-francais.pdf'))
    ->language('fr')
    ->summarize()
    ->result()
    ->summary;

$chinesesJson = AIDocs::pdf(storage_path('app/documents/chinese-contract.pdf'))
    ->language('zh')
    ->toJson();

// ==========================================================
// 15. Custom Prompts throughout the pipeline
// ==========================================================

// Custom summarization prompt
$customSummary = AIDocs::pdf(storage_path('app/documents/meeting-notes.pdf'))
    ->summarize('Give me a bullet-point action item list from this meeting.')
    ->result()
    ->summary;

// Custom ask question
$riskAssessment = AIDocs::pdf(storage_path('app/documents/contract.pdf'))
    ->ask('List all clauses that could be considered legal risks for the vendor.');
