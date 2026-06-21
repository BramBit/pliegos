<?php

namespace App\Services\AI;

use App\Services\AI\Strategies\ChatStrategy;
use App\Services\AI\Strategies\OllamaChatStrategy;
use App\Services\AI\Strategies\OpenAIChatStrategy;

class ChatService
{
    private ChatStrategy $strategy;

    public function __construct()
    {
        $this->strategy = $this->resolveStrategy();
    }

    public function generate(string $systemPrompt, string $userMessage): string
    {
        return $this->strategy->generate($systemPrompt, $userMessage);
    }

    private function resolveStrategy(): ChatStrategy
    {
        return match (config('app.env')) {
            'production' => new OpenAIChatStrategy(),
            default       => new OllamaChatStrategy(),
        };
    }
}
