<?php

namespace App\Services;

use App\Models\EventoCecoco;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TiempoRespuestaCecocoService
{
    /**
     * Calcula, para cada evento con expediente consultado, los minutos entre que el
     * recurso más rápido pasa a "En desplazamiento" y a "En atención" según el
     * timeline scrapeado de CECOCO (ver DetalleExpedienteCecoco::detalle_json).
     * Solo cubre eventos cuyo expediente ya fue consultado al menos una vez.
     *
     * @return Collection<int, array{evento_id: int, nro_expediente: string, fecha_hora: Carbon, tipo_servicio: string, recurso: string, recursos_totales: int, minutos: float}>
     */
    public function calcular(Builder $query): Collection
    {
        $resultados = collect();

        $query->with('detalle')
            ->whereHas('detalle')
            ->chunkById(500, function (EloquentCollection $eventos) use (&$resultados): void {
                foreach ($eventos as $evento) {
                    $item = $this->tiempoDelEvento($evento);
                    if ($item !== null) {
                        $resultados->push($item);
                    }
                }
            });

        return $resultados;
    }

    /**
     * @return array{evento_id: int, nro_expediente: string, fecha_hora: Carbon, tipo_servicio: string, recurso: string, recursos_totales: int, minutos: float}|null
     */
    private function tiempoDelEvento(EventoCecoco $evento): ?array
    {
        $timeline = $evento->detalle->detalle_json['timeline'] ?? null;
        if (!is_array($timeline)) {
            return null;
        }

        $marcasPorRecurso = [];
        foreach ($timeline as $paso) {
            $estado = $paso['estado'] ?? null;
            $fechaHora = $paso['fecha_hora'] ?? null;
            if (!is_string($estado) || !is_string($fechaHora) || !str_contains($estado, ',')) {
                continue;
            }

            [$recurso, $marcaTexto] = array_map('trim', explode(',', $estado, 2));
            $marca = $this->normalizarMarca($marcaTexto);
            if ($marca === null) {
                continue;
            }

            $timestamp = $this->parsearFecha($fechaHora);
            if ($timestamp === null) {
                continue;
            }

            // Primera ocurrencia de cada marca por recurso (ignora reasignaciones).
            $marcasPorRecurso[$recurso][$marca] ??= $timestamp;
        }

        $minutosPorRecurso = [];
        foreach ($marcasPorRecurso as $recurso => $marcas) {
            if (!isset($marcas['desplazamiento'], $marcas['atencion']) || $this->esRecursoFijo($recurso)) {
                continue;
            }

            $minutos = $marcas['desplazamiento']->diffInSeconds($marcas['atencion']) / 60;
            if ($minutos <= 0 || $minutos > 24 * 60) {
                continue; // dato inconsistente (reloj, reasignación, etc.)
            }

            $minutosPorRecurso[$recurso] = $minutos;
        }

        if ($minutosPorRecurso === []) {
            return null;
        }

        $recursoMasRapido = (string) array_search(min($minutosPorRecurso), $minutosPorRecurso, true);

        return [
            'evento_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente ?: '-',
            'fecha_hora' => $evento->fecha_hora,
            'tipo_servicio' => $evento->tipo_servicio ?: '(sin tipo)',
            'recurso' => $recursoMasRapido,
            'recursos_totales' => count($minutosPorRecurso),
            'minutos' => round($minutosPorRecurso[$recursoMasRapido], 1),
        ];
    }

    /**
     * Descarta bases, despachos, centrales telefónicas, cámaras y el COE: son puestos
     * fijos que también pasan por el estado "En desplazamiento"/"En atención" en el
     * timeline, pero no representan un móvil llegando físicamente al lugar.
     */
    private function esRecursoFijo(string $recurso): bool
    {
        $texto = mb_strtolower(trim($recurso));

        foreach (['base', 'desp', 'cam', 'banc', 's. telef', 's.telef', 'coe'] as $prefijo) {
            if (str_starts_with($texto, $prefijo)) {
                return true;
            }
        }

        return false;
    }

    private function normalizarMarca(string $marca): ?string
    {
        $texto = strtr(mb_strtolower(trim($marca)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        return match ($texto) {
            'en desplazamiento' => 'desplazamiento',
            'en atencion' => 'atencion',
            default => null,
        };
    }

    private function parsearFecha(string $fechaHora): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $fechaHora);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
