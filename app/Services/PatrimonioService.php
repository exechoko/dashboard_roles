<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Historico;
use App\Models\PatrimonioCargo;
use App\Models\TipoMovimiento;
use Illuminate\Support\Facades\Log;

class PatrimonioService
{
    // Cache de IDs de tipos de movimiento para evitar consultas repetidas
    private $tipoMovIds = [];

    /**
     * Obtener los IDs de tipos de movimiento relevantes
     */
    private function getTipoMovimientoIds()
    {
        if (empty($this->tipoMovIds)) {
            $this->tipoMovIds = [
                'patrimonial'         => TipoMovimiento::where('nombre', 'Movimiento patrimonial')->value('id'),
                'inst_completa'       => TipoMovimiento::where('nombre', 'Instalación completa')->value('id'),
                'desinst_completa'    => TipoMovimiento::where('nombre', 'Desinstalación completa')->value('id'),
                'provisorio'          => TipoMovimiento::where('nombre', 'Provisorio')->value('id'),
                'revision'            => TipoMovimiento::where('nombre', 'Revisión')->value('id'),
                'devolucion'          => TipoMovimiento::where('nombre', 'Devolución')->value('id'),
                'devolucion_dep'      => TipoMovimiento::where('nombre', 'Devolución a dependencia')->value('id'),
                'reemplazo'           => TipoMovimiento::where('nombre', 'Reemplazo')->value('id'),
                'recambio'            => TipoMovimiento::where('nombre', 'Recambio')->value('id'),
                'baja'                => TipoMovimiento::where('nombre', 'Baja')->value('id'),
                'extraviado'          => TipoMovimiento::where('nombre', 'Extraviado')->value('id'),
                'devolver_temporal'   => TipoMovimiento::where('nombre', 'Devolver equipo temporal')->value('id'),
            ];
        }

        return $this->tipoMovIds;
    }

    /**
     * Procesar un movimiento y aplicar la lógica patrimonial correspondiente
     *
     * @param FlotaGeneral $flota
     * @param int $tipoMovimientoId
     * @param array $datos (destino_id, historico_id, fecha_asignacion)
     * @return PatrimonioCargo|null El cargo creado (si aplica)
     */
    public function procesarMovimiento(FlotaGeneral $flota, int $tipoMovimientoId, array $datos = []): ?PatrimonioCargo
    {
        $ids = $this->getTipoMovimientoIds();

        // Movimiento patrimonial → crear cargo
        if ($tipoMovimientoId === $ids['patrimonial']) {
            return $this->crearCargo(
                $flota,
                $datos['destino_id'] ?? $flota->destino_id,
                $datos['historico_id'] ?? null,
                $datos['fecha_asignacion'] ?? null
            );
        }

        // Movimientos que ROMPEN el patrimonio
        if (in_array($tipoMovimientoId, [
            $ids['desinst_completa'],
            $ids['baja'],
            $ids['extraviado'],
        ])) {
            $this->limpiarPatrimonio($flota);
            return null;
        }

        // Movimientos transitorios → NO tocar patrimonio
        // (provisorio, revisión, instalación completa, devolución a dependencia, reprogramación)
        // No hacen nada patrimonial

        return null;
    }

    /**
     * Crear un cargo patrimonial para un equipo
     */
    public function crearCargo(FlotaGeneral $flota, int $destinoId, ?int $historicoId = null, $fecha = null): PatrimonioCargo
    {
        $cargo = PatrimonioCargo::create([
            'equipo_id'       => $flota->equipo_id,
            'destino_id'      => $destinoId,
            'historico_id'    => $historicoId,
            'estado'          => 'pendiente',
            'usuario_creador' => auth()->user()->name ?? 'Sistema',
        ]);

        // Marcar el equipo como patrimoniado
        $flota->patrimoniar($destinoId, $cargo->id, $fecha);

        Log::info("Cargo patrimonial #{$cargo->id} creado para equipo #{$flota->equipo_id} en destino #{$destinoId}");

        return $cargo;
    }

    /**
     * Transferir patrimonio de un equipo a otro (para reemplazos)
     * El equipo nuevo hereda el patrimonio del viejo
     */
    public function transferirPatrimonio(FlotaGeneral $flotaVieja, FlotaGeneral $flotaNueva): ?PatrimonioCargo
    {
        if (!$flotaVieja->patrimoniado) {
            return null;
        }

        $destinoPatrimonial = $flotaVieja->destino_patrimonial_id;

        // El equipo viejo pierde patrimonio
        $this->limpiarPatrimonio($flotaVieja);

        // El equipo nuevo hereda el patrimonio (con cargo nuevo pendiente)
        $cargo = $this->crearCargo($flotaNueva, $destinoPatrimonial);

        Log::info("Patrimonio transferido de equipo #{$flotaVieja->equipo_id} a #{$flotaNueva->equipo_id}");

        return $cargo;
    }

    /**
     * Limpiar patrimonio de un equipo
     */
    public function limpiarPatrimonio(FlotaGeneral $flota): void
    {
        if (!$flota->patrimoniado) {
            return;
        }

        Log::info("Patrimonio limpiado para equipo #{$flota->equipo_id}");

        $flota->despatrimoniar();
    }

    /**
     * Verificar si un tipo de movimiento es patrimonial
     */
    public function esMovimientoPatrimonial(int $tipoMovimientoId): bool
    {
        $ids = $this->getTipoMovimientoIds();
        return $tipoMovimientoId === $ids['patrimonial'];
    }

    /**
     * Verificar si un tipo de movimiento rompe el patrimonio
     */
    public function rompePatrimonio(int $tipoMovimientoId): bool
    {
        $ids = $this->getTipoMovimientoIds();
        return in_array($tipoMovimientoId, [
            $ids['desinst_completa'],
            $ids['baja'],
            $ids['extraviado'],
        ]);
    }
}
