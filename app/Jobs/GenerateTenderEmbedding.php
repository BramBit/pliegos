<?php

namespace App\Jobs;

use App\Models\Tender;
use App\Models\TenderEmbedding;
use App\Services\AI\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateTenderEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tenderId
    ) {
    }

    public function handle(EmbeddingService $embeddingService): void
    {
        $tender = Tender::find($this->tenderId);

        if (!$tender || $tender->indexed) {
            return;
        }

        $content = trim($tender->title . '. ' . $tender->description);

        try {
            $vector = $embeddingService->embed($content);

            $embedding = TenderEmbedding::create([
                'tender_id' => $tender->id,
                'content' => $content,
            ]);

            // pgvector requiere formato especial, lo seteamos vía SQL directo
            DB::statement(
                'UPDATE tender_embeddings SET embedding = ? WHERE id = ?',
                [$this->vectorToString($vector), $embedding->id]
            );

            $tender->update(['indexed' => true]);

        } catch (\Exception $e) {
            Log::error('GenerateTenderEmbedding failed', [
                'tender_id' => $this->tenderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function vectorToString(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }
}
