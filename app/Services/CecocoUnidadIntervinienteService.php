<?php

namespace App\Services;

use App\Models\CecocoRecursoAlias;
use App\Models\Destino;
use Illuminate\Support\Collection;

/**
 * Resuelve, a partir del detalle de "Trámites" de un expediente CECOCO, qué
 * dependencia (Destino) intervino: cruza la "Unidad" de cada trámite contra los
 * alias activos de {@see CecocoRecursoAlias} y sube hasta la dependencia "raíz"
 * (división/departamental/jefatura/subjefatura) para agrupar secciones y
 * destacamentos bajo su dependencia mayor.
 *
 * Hoy solo hay alias cargados para la flota de la División 911 y Videovigilancia
 * (ver cecoco:generar-alias-911), por eso {@see dependenciasDisponibles()} devuelve
 * una sola dependencia. Para sumar otra, basta con cargar sus alias en
 * cecoco_recurso_aliases (comando análogo o /cecoco/recursos-alias/): no requiere
 * cambios de código.
 */
class CecocoUnidadIntervinienteService
{
    private const NIVELES_RAIZ = ['division', 'departamental', 'jefatura', 'subjefatura'];

    /**
     * @return Collection<string, CecocoRecursoAlias> alias CECOCO normalizado (ej. "P1022") => alias con recurso.destino cargado
     */
    public function aliasActivos(): Collection
    {
        return CecocoRecursoAlias::where('activo', true)
            ->with('recurso.destino')
            ->get()
            ->keyBy(fn (CecocoRecursoAlias $alias) => mb_strtoupper($alias->alias_cecoco));
    }

    /**
     * Dependencias "raíz" que tienen al menos un recurso con alias activo cargado en CECOCO.
     *
     * @return Collection<int, Destino>
     */
    public function dependenciasDisponibles(): Collection
    {
        return $this->aliasActivos()
            ->map(fn (CecocoRecursoAlias $alias) => optional($alias->recurso)->destino)
            ->filter()
            ->map(fn (Destino $destino) => $this->raiz($destino))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    /**
     * IDs de destino a incluir al filtrar por una dependencia (ella misma + descendientes).
     *
     * @return array<int, int>
     */
    public function idsDependencia(int $destinoId): array
    {
        return Destino::obtenerTodosLosHijos($destinoId)->all();
    }

    /**
     * Extrae las "Unidad" del detalle de trámites de un expediente y devuelve los
     * destino_id (sin duplicar) de las que se pudieron resolver contra un alias activo.
     *
     * @param array<string, mixed> $detalleJson
     * @param Collection<string, CecocoRecursoAlias> $aliasActivos
     * @return array<int, int>
     */
    public function destinosIntervinientes(array $detalleJson, Collection $aliasActivos): array
    {
        $destinos = [];

        foreach ($detalleJson['tramites'] ?? [] as $tramite) {
            $unidad = trim((string) ($tramite['unidad'] ?? ''));
            if ($unidad === '' || $unidad === '-') {
                continue;
            }

            if (!preg_match('/^([a-zA-Z]{1,3})\s*0*(\d+)\s*$/', $unidad, $m)) {
                continue;
            }

            $aliasNormalizado = mb_strtoupper($m[1]) . (int) $m[2];
            $destinoId = optional(optional($aliasActivos->get($aliasNormalizado))->recurso)->destino_id;

            if ($destinoId) {
                $destinos[] = $destinoId;
            }
        }

        return array_unique($destinos);
    }

    private function raiz(Destino $destino): Destino
    {
        $actual = $destino;

        while ($actual->parent_id && !in_array($actual->tipo, self::NIVELES_RAIZ, true)) {
            $padre = $actual->padre;
            if (!$padre) {
                break;
            }
            $actual = $padre;
        }

        return $actual;
    }
}
