<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAndSendReport;
use App\Models\TenderSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function generate(Request $request, TenderSearch $tenderSearch): JsonResponse
    {
        if ($tenderSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        GenerateAndSendReport::dispatch($tenderSearch->id);

        return response()->json([
            'message' => 'Report generation started. You will receive it by email shortly.',
        ], 202);
    }
}
