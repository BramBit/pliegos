<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenderSearch;
use App\Services\Search\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SemanticSearchController extends Controller
{
    public function __construct(
        private SemanticSearchService $searchService
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

        return response()->json([
            'question' => $validated['question'],
            'results'  => $results,
        ]);
    }
}
