<?php

namespace App\Services\AI\Strategies;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaEmbeddingStrategy implements EmbeddingStrategy
{
    public function embed(string $text): array
    {
        $response = Http::timeout(60)->post('http://localhost:11434/api/embeddings', [
            'model'  => 'nomic-embed-text',
            'prompt' => $text,
        ]);

        if ($response->failed()) {
            Log::error('OllamaEmbeddingStrategy: request failed', [
                'status' => $response->status(),
            ]);
            throw new \RuntimeException('Failed to generate embedding with Ollama.');
        }

        return $response->json('embedding');
    }
}
