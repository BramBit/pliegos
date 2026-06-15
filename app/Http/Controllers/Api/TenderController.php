<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Models\TenderSearch;
use App\Services\Secop\SecopService;
use App\Jobs\GenerateTenderEmbedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderController extends Controller
{
    public function __construct(
        private SecopService $secopService
    ) {
    }

    public function search(Request $request, TenderSearch $tenderSearch): JsonResponse
    {
        if ($tenderSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $existingCount = Tender::where('sector', $tenderSearch->sector)->count();

        if ($existingCount === 0) {
            $newTenders = $this->secopService->fetchAndStore(
                sector: $tenderSearch->sector,
                budgetMin: $tenderSearch->budget_min,
                budgetMax: $tenderSearch->budget_max,
            );

            foreach ($newTenders as $tender) {
                GenerateTenderEmbedding::dispatch($tender->id);
            }
        }

        $tenders = Tender::where('sector', $tenderSearch->sector)
            ->when($tenderSearch->budget_min, fn($q) => $q->where('budget', '>=', $tenderSearch->budget_min))
            ->when($tenderSearch->budget_max, fn($q) => $q->where('budget', '<=', $tenderSearch->budget_max))
            ->orderByDesc('published_at')
            ->get();

        $tenderSearch->update(['status' => 'completed']);

        return response()->json([
            'search' => $tenderSearch,
            'count' => $tenders->count(),
            'tenders' => $tenders,
        ]);
    }
}
