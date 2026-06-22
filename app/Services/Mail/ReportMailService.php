<?php

namespace App\Services\Mail;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportMailService
{
    public function send(User $user, Report $report): bool
    {
        try {
            $response = Http::withToken(config('services.resend.api_key'))
                ->post('https://api.resend.com/emails', [
                    'from'    => 'Pliegos <onboarding@resend.dev>',
                    'to'      => [$user->email],
                    'subject' => 'Tu reporte de licitaciones está listo',
                    'html'    => $this->buildHtml($user, $report),
                ]);

            if ($response->failed()) {
                Log::error('ReportMailService: failed to send email', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('ReportMailService: exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function buildHtml(User $user, Report $report): string
    {
        $summary = nl2br(e($report->summary));

        return <<<HTML
            <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Hola {$user->name},</h2>
                <p>Aquí está el resumen de las licitaciones encontradas para tu búsqueda:</p>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">
                    {$summary}
                </div>
                <p style="margin-top: 20px; color: #888; font-size: 12px;">
                    Generado automáticamente por Pliegos.
                </p>
            </div>
        HTML;
    }
}
