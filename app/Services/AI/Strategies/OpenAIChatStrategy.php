<?php

namespace App\Services\AI\Strategies;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIChatStrategy implements ChatStrategy
{
    public function generate(string $systemPrompt, string $userMessage): string
    {
        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if ($response->failed()) {
            Log::error('OpenAIChatStrategy: request failed', ['status' => $response->status()]);
            throw new \RuntimeException('Failed to generate chat response with OpenAI.');
        }

        return $response->json('choices.0.message.content');
    }
}
