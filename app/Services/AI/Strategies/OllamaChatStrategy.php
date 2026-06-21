<?php

namespace App\Services\AI\Strategies;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaChatStrategy implements ChatStrategy
{
    public function generate(string $systemPrompt, string $userMessage): string
    {
        $response = Http::timeout(120)->post('http://localhost:11434/api/chat', [
            'model'    => 'llama3.1',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'stream' => false,
        ]);

        if ($response->failed()) {
            Log::error('OllamaChatStrategy: request failed', ['status' => $response->status()]);
            throw new \RuntimeException('Failed to generate chat response with Ollama.');
        }

        return $response->json('message.content');
    }
}
