<?php

use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SemanticSearchController;
use App\Http\Controllers\Api\TenderSearchController;
use App\Http\Controllers\Api\TenderController;
use App\Http\Controllers\Auth\AuthController;
use App\Services\Secop\SecopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-secop', function () {
    $service = new SecopService();
    $results = $service->fetchTenders('tecnología', 50000000, 500000000);
    return response()->json([
        'count' => $results->count(),
        'results' => $results->take(2),
    ]);
});

// Rutas públicas
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Route::get('/searches', [TenderSearch::class, 'index']);
    // Route::post('/searches', [TenderSearch::class, 'store']);
    // Route::get('/searches/{tenderSearch}', [TenderSearch::class, 'show']);

    // Tender searches
    Route::get('/searches', [TenderSearchController::class, 'index']);
    Route::post('/searches', [TenderSearchController::class, 'store']);
    Route::get('/searches/{tenderSearch}', [TenderSearchController::class, 'show']);
    Route::get('/searches/{tenderSearch}/tenders', [TenderController::class, 'search']);

    // Semantic searches
    Route::post('/searches/{tenderSearch}/ask', [SemanticSearchController::class, 'ask']);

    // Report
    Route::post('/searches/{tenderSearch}/report', [ReportController::class, 'generate']);
});
