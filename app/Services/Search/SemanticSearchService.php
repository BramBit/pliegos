<?php

namespace App\Services\Search;

use App\Models\Tender;
use App\Services\AI\EmbeddingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SemanticSearchService
{
    public function __construct(
        private EmbeddingService $embeddingService
    ) {}

    public function search(string $query, string $sector, int $limit = 10): Collection
    {
        $queryVector = $this->embeddingService->embed($query);
        $vectorString = $this->vectorToString($queryVector);

        $results = DB::select("
            SELECT
                tenders.*,
                tender_embeddings.embedding <=> ? AS distance
            FROM tender_embeddings
            JOIN tenders ON tenders.id = tender_embeddings.tender_id
            WHERE tenders.sector = ?
            ORDER BY distance ASC
            LIMIT ?
        ", [$vectorString, $sector, $limit]);

        return collect($results);
    }

    private function vectorToString(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }
}
