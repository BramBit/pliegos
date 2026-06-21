<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenderSearch;
use App\Services\AI\ChatService;
use App\Services\Search\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SemanticSearchController extends Controller
{
    public function __construct(
        private SemanticSearchService $searchService,
        private ChatService $chatService
    ) {}

    public function ask(Request $request, TenderSearch $tenderSearch): JsonResponse
    {
        if ($tenderSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $results = $this->searchService->search(
            query: $validated['question'],
            sector: $tenderSearch->sector,
            limit: 5
        );

        $context = $this->buildContext($results);
        $systemPrompt = $this->buildSystemPrompt($tenderSearch);

        $answer = $this->chatService->generate(
            systemPrompt: $systemPrompt,
            userMessage: "Contexto de licitaciones:\n\n{$context}\n\nPregunta del usuario: {$validated['question']}"
        );

        return response()->json([
            'question' => $validated['question'],
            'answer'   => $answer,
            'sources'  => $results,
        ]);
    }

    private function buildContext($results): string
    {
        return $results->map(function ($tender, $index) {
            return ($index + 1) . ". {$tender->title}\n"
                . "Entidad: {$tender->entity}\n"
                . "Presupuesto: $" . number_format($tender->budget) . "\n"
                . "Estado: {$tender->status}\n"
                . "URL: {$tender->url}\n";
        })->implode("\n");
    }

    private function buildSystemPrompt(TenderSearch $tenderSearch): string
    {
        return "Eres un asistente experto en contratación pública colombiana que ayuda a la empresa "
            . "'{$tenderSearch->company}' del sector '{$tenderSearch->sector}' a encontrar oportunidades de negocio. "
            . "Responde basándote únicamente en el contexto de licitaciones proporcionado. "
            . "Sé claro, directo y profesional. Si una licitación es relevante, explica por qué.";
    }
}
