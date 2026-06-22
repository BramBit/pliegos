<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\Tender;
use App\Models\TenderSearch;
use App\Services\AI\ChatService;
use App\Services\Mail\ReportMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAndSendReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tenderSearchId
    ) {}

    public function handle(ChatService $chatService, ReportMailService $mailService): void
    {
        $tenderSearch = TenderSearch::with('user')->find($this->tenderSearchId);

        if (!$tenderSearch) {
            return;
        }

        $tenders = Tender::where('sector', $tenderSearch->sector)
            ->when($tenderSearch->budget_min, fn($q) => $q->where('budget', '>=', $tenderSearch->budget_min))
            ->when($tenderSearch->budget_max, fn($q) => $q->where('budget', '<=', $tenderSearch->budget_max))
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        if ($tenders->isEmpty()) {
            return;
        }

        $context = $this->buildContext($tenders);

        $summary = $chatService->generate(
            systemPrompt: "Eres un asistente experto en contratación pública colombiana. Genera un resumen "
                . "ejecutivo claro y profesional en español de las licitaciones encontradas para la empresa "
                . "'{$tenderSearch->company}' del sector '{$tenderSearch->sector}'. Incluye las más relevantes, "
                . "sus montos y sugerencias sobre cuáles priorizar. Sé conciso pero completo.",
            userMessage: "Licitaciones encontradas:\n\n{$context}"
        );

        $report = Report::create([
            'tender_search_id' => $tenderSearch->id,
            'summary'          => $summary,
            'tender_ids'       => $tenders->pluck('id')->toArray(),
            'status'           => 'pending',
        ]);

        $sent = $mailService->send($tenderSearch->user, $report);

        $report->update([
            'status'  => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
        ]);
    }

    private function buildContext($tenders): string
    {
        return $tenders->map(function ($tender, $index) {
            return ($index + 1) . ". {$tender->title}\n"
                . "Entidad: {$tender->entity}\n"
                . "Presupuesto: $" . number_format($tender->budget) . "\n"
                . "Ciudad: {$tender->city}\n";
        })->implode("\n");
    }
}
