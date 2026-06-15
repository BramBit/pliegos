<?php

namespace App\Services\AI\Strategies;

interface EmbeddingStrategy
{
    public function embed(string $text): array;
}
