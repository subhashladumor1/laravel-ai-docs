<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Services;

use Subhashladumor1\LaravelAiDocs\Processors\TextChunker;
use Subhashladumor1\LaravelAiDocs\Providers\Contracts\AIProviderInterface;

/**
 * Ask-PDF service implementing a lightweight RAG pipeline.
 *
 * Steps:
 *  1. Chunk document text into overlapping token windows.
 *  2. Retrieve the most relevant chunks for the query.
 *  3. Build a context-augmented prompt.
 *  4. Send to the AI provider and return the answer.
 */
class AskPDFService
{
    public function __construct(
        private readonly TextChunker $chunker,
    ) {
    }

    /**
     * Ask a natural-language question about a document.
     *
     * @param  AIProviderInterface  $provider
     * @param  string  $documentText  The full extracted text.
     * @param  string  $question      The user's question.
     * @param  int  $topK            Number of chunks to include as context.
     * @return string  The AI's answer.
     */
    public function ask(
        AIProviderInterface $provider,
        string $documentText,
        string $question,
        int $topK = 5,
    ): string {
        if (trim($documentText) === '') {
            return 'The document appears to be empty. No answer can be generated.';
        }

        // Step 1 – chunk the document
        $chunks = $this->chunker->chunk($documentText);

        // Step 2 – retrieve most relevant chunks
        $relevant = $this->chunker->retrieveRelevant($chunks, $question, $topK);

        // Step 3 – build context-augmented prompt
        $context = implode("\n\n---\n\n", $relevant);

        $prompt = <<<PROMPT
        You are a document question-answering assistant. Use ONLY the provided context to answer the question.
        If the answer is not present in the context, say "I could not find this information in the document."

        Context:
        {$context}

        Question: {$question}

        Answer:
        PROMPT;

        // Step 4 – get AI response
        return $provider->generateText($prompt);
    }
}
