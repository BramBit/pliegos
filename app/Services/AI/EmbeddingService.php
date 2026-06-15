<?php

namespace App\Services\AI;

use App\Services\AI\Strategies\EmbeddingStrategy;
use App\Services\AI\Strategies\OllamaEmbeddingStrategy;
use App\Services\AI\Strategies\OpenAIEmbeddingStrategy;

class EmbeddingService
{
    private EmbeddingStrategy $strategy;

    public function __construct()
    {
        $this->strategy = $this->resolveStrategy();
    }

    public function embed(string $text): array
    {
        return $this->strategy->embed($text);
    }

    private function resolveStrategy(): EmbeddingStrategy
    {
        return match (config('app.env')) {
            'production' => new OpenAIEmbeddingStrategy(),
            default       => new OllamaEmbeddingStrategy(),
        };
    }
}
