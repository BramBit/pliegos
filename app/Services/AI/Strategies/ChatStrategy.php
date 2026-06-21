<?php

namespace App\Services\AI\Strategies;

interface ChatStrategy
{
    public function generate(string $systemPrompt, string $userMessage): string;
}
