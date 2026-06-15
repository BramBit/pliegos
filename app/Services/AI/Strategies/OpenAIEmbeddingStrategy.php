<?php

namespace App\Services\AI\Strategies;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIEmbeddingStrategy implements EmbeddingStrategy
{
    public function embed(string $text): array
    {
        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

        if ($response->failed()) {
            Log::error('OpenAIEmbeddingStrategy: request failed', [
                'status' => $response->status(),
            ]);
            throw new \RuntimeException('Failed to generate embedding with OpenAI.');
        }

        return $response->json('data.0.embedding');
    }
}
