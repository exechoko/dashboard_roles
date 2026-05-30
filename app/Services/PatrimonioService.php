<?php

namespace App\Services;

use App\Models\FlotaGeneral;
use App\Models\Historico;
use App\Models\PatrimonioCargo;
use App\Models\PatrimonioCargoMovimiento;
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

        // La baja es la única salida directa del patrimonio. Desinstalación completa
        // y extraviado conservan el cargo patrimonial de la dependencia.
        if (in_array($tipoMovimientoId, [
            $ids['baja'],
        ])) {
            $this->limpiarPatrimonio($flota, $datos['historico_id'] ?? null, null, 'Baja');
            return null;
        }

        // Movimientos transitorios o de seguimiento no tocan patrimonio.

        return null;
    }

    /**
     * Crear o reutilizar un cargo patrimonial pendiente para una dependencia.
     */
    public function crearCargo(FlotaGeneral $flota, int $destinoId, ?int $historicoId = null, $fecha = null): PatrimonioCargo
    {
        $this->registrarSalidaCargoFirmado($flota, $destinoId, $historicoId);

        $cargo = PatrimonioCargo::where('destino_id', $destinoId)
            ->where('estado', 'pendiente')
            ->latest()
            ->first();

        if (!$cargo) {
            $cargo = PatrimonioCargo::create([
                'equipo_id'       => $flota->equipo_id,
                'destino_id'      => $destinoId,
                'historico_id'    => $historicoId,
                'estado'          => 'pendiente',
                'usuario_creador' => auth()->user()->name ?? 'Sistema',
            ]);
        }

        $flota->patrimoniar($destinoId, $cargo->id, $fecha);

        Log::info("Equipo #{$flota->equipo_id} agregado al cargo patrimonial #{$cargo->id} en destino #{$destinoId}");

        return $cargo;
    }

    /**
     * Transferir patrimonio de un equipo a otro (para reemplazos)
     * El equipo nuevo hereda el patrimonio del viejo
     */
    public function transferirPatrimonio(FlotaGeneral $flotaVieja, FlotaGeneral $flotaNueva, ?int $historicoId = null, $fecha = null): ?PatrimonioCargo
    {
        if (!$flotaVieja->patrimoniado) {
            return null;
        }

        $destinoPatrimonial = $flotaVieja->destino_patrimonial_id;

        // El equipo viejo pierde patrimonio
        $this->limpiarPatrimonio($flotaVieja);

        // El equipo nuevo hereda el patrimonio (con cargo nuevo pendiente)
        $cargo = $this->crearCargo($flotaNueva, $destinoPatrimonial, $historicoId, $fecha);

        Log::info("Patrimonio transferido de equipo #{$flotaVieja->equipo_id} a #{$flotaNueva->equipo_id}");

        return $cargo;
    }

    /**
     * Limpiar patrimonio de un equipo
     */
    public function limpiarPatrimonio(FlotaGeneral $flota, ?int $historicoId = null, ?int $destinoDestinoId = null, ?string $motivo = null): void
    {
        if (!$flota->patrimoniado) {
            return;
        }

        $this->registrarSalidaCargoFirmado($flota, $destinoDestinoId, $historicoId, $motivo);

        Log::info("Patrimonio limpiado para equipo #{$flota->equipo_id}");

        $flota->despatrimoniar();
    }

    /**
     * Si el equipo está actualmente en un cargo FIRMADO, deja registrado que
     * salió de ese cargo (a qué dependencia / por qué motivo) antes de que se
     * reasigne o limpie su patrimonio. Los cargos pendientes no son actas, se ignoran.
     */
    private function registrarSalidaCargoFirmado(FlotaGeneral $flota, ?int $destinoDestinoId, ?int $historicoId, ?string $motivo = null): void
    {
        if (!$flota->cargo_id) {
            return;
        }

        $cargo = $flota->cargo()->first();

        if (!$cargo || $cargo->estado !== 'firmado') {
            return;
        }

        $tipoMovimientoId = null;
        $fecha = now();

        if ($historicoId) {
            $historico = Historico::find($historicoId);

            if ($historico) {
                $tipoMovimientoId = $historico->tipo_movimiento_id;
                $fecha = $historico->fecha_asignacion ?? $fecha;
            }
        }

        if (!$motivo && $tipoMovimientoId) {
            $motivo = TipoMovimiento::where('id', $tipoMovimientoId)->value('nombre');
        }

        PatrimonioCargoMovimiento::create([
            'cargo_id'           => $cargo->id,
            'equipo_id'          => $flota->equipo_id,
            'flota_id'           => $flota->id,
            'destino_origen_id'  => $flota->destino_patrimonial_id,
            'destino_destino_id' => $destinoDestinoId,
            'historico_id'       => $historicoId,
            'tipo_movimiento_id' => $tipoMovimientoId,
            'motivo'             => $motivo,
            'usuario'            => auth()->user()->name ?? 'Sistema',
            'fecha'              => $fecha,
        ]);

        Log::info("Equipo #{$flota->equipo_id} salió del cargo firmado #{$cargo->id}");
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
            $ids['baja'],
        ]);
    }
}
