<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenderSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderSearchController extends Controller
{
    public function store(Request $request):JsonResponse
    {
        $validated = $request ->validate([
            'company'    => 'required|string|max:255',
            'sector'     => 'required|string|max:225',
            'budget_min' => 'nullable|integer|min:0',
            'budget_max' => 'nullable|integer|min:0|gte:budget_min'
        ]);

        $search = TenderSearch::create([
            'user_id'    => $request->user()->id,
            'company'    => $validated['company'],
            'sector'     => $validated['sector'],
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'] ?? null,
            'status'     => 'pending',
        ]);

        return response() ->json([
            'message' => 'Search created successfully.',
            'search'  => $search,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $searches = TenderSearch::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($searches);
    }

    public function show(Request $request, TenderSearch $tenderSearch): JsonResponse
    {
        if ($tenderSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($tenderSearch);
    }
}
