<?php

namespace App\Services\Secop;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SecopService
{
    private const BASE_URL = 'https://www.datos.gov.co/resource/p6dx-8zbt.json';
    private const LIMIT = 50;

    public function fetchTenders(
        string $sector,
        ?int $budgetMin = null,
        ?int $budgetMax = null
    ): Collection {
        try {
            $params = [
                '$limit'  => self::LIMIT,
                '$where'  => $this->buildWhereClause($budgetMin, $budgetMax),
                '$q'      => $sector,
            ];

            $response = Http::timeout(30) ->get(self::BASE_URL, $params);

            if ($response->failed()) {
                Log::error('SecopService: API request failed', [
                    'status' => $response->status(),
                    'sector' => $sector,
                ]);
                return collect();
            }

            return collect($response->json()) ->map(fn($item) => $this->normalize($item));

        } catch (\Exception $e) {
            Log::error('SecopService: Exception', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    public function fetchAndStore(
        string $sector,
        ?int $budgetMin = null,
        ?int $budgetMax = null
    ): Collection {
        $tenders = $this->fetchTenders($sector, $budgetMin, $budgetMax);

        $stored = $tenders->map(function (array $data) use ($sector) {
            return \App\Models\Tender::firstOrCreate(
                ['process_id' => $data['process_id']],
                [
                    'title'         => $data['title'],
                    'description'  => $data['description'],
                    'entity'        => $data['entity'],
                    'city'          => $data['city'],
                    'department'    => $data['department'],
                    'budget'        => $data['budget'],
                    'contract_type' => $data['contract_type'],
                    'status'        => $data['status'],
                    'published_at'  => $data['published_at'],
                    'url'           => $data['url'],
                    'sector'        => $sector,
                    'indexed'       => false,
                ]
            );
        });

        return $stored;
    }

    private function buildWhereClause(?int $budgetMin, ?int $budgetMax): string
    {
        $conditions = [];

        if ($budgetMin !== null) {
            $conditions[] = "precio_base >= {$budgetMin}";
        }

        if ($budgetMax !== null) {
            $conditions[] = "precio_base <= {$budgetMax}";
        }

        return !empty($conditions)
            ? implode(' AND ', $conditions)
            : 'precio_base > 0';
    }

    private function normalize(array $item): array
    {
        return [
            'process_id'  => $item['id_del_proceso'] ?? null,
            'title'       => $item['nombre_del_procedimiento'] ?? null,
            'description' => $item['descripci_n_del_procedimiento'] ?? null,
            'entity'      => $item['entidad'] ?? null,
            'budget'      => $item['precio_base'] ?? null,
            'city'        => $item['ciudad_entidad'] ?? null,
            'department'  => $item['departamento_entidad'] ?? null,
            'contract_type' => $item['tipo_de_contrato'] ?? null,
            'status'      => $item['estado_del_procedimiento'] ?? null,
            'published_at' => $item['fecha_de_publicacion_del'] ?? null,
            'url'         => $item['urlproceso']['url'] ?? null,
        ];
    }
}
