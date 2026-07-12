<?php

namespace App\Services;

use App\Models\ArmaMotivo;
use App\Models\ArmaRetencion;
use App\Models\Auditoria;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArmaRetencionService
{
    /**
     * Calcular días restantes para elevación.
     * Solo aplica cuando tipo = RETENCIÓN y el motivo tiene días > 0.
     */
    public function calcularDiasRestantes(ArmaRetencion $retencion): ?int
    {
        if ($retencion->tipo !== 'RETENCIÓN') {
            return null;
        }

        if (!$retencion->motivo || $retencion->motivo->dias <= 0) {
            return null;
        }

        if (!$retencion->fecha_posesion) {
            return null;
        }

        $fechaLimite = $retencion->fecha_posesion->copy()->addDays($retencion->motivo->dias);
        $dias = max(0, Carbon::today()->diffInDays($fechaLimite, false));

        return (int) $dias;
    }

    /**
     * Calcular estado automático según las reglas de negocio.
     */
    public function calcularEstado(ArmaRetencion $retencion): string
    {
        if ($retencion->fecha_devolucion) {
            return 'DEVUELTA';
        }

        if ($retencion->fecha_elevacion) {
            return 'EN_JEF_CENTRAL';
        }

        return 'EN_ARMERIA';
    }

    /**
     * Crear un nuevo registro de retención de arma.
     */
    public function crear(array $datos): ArmaRetencion
    {
        DB::beginTransaction();

        try {
            $motivo = ArmaMotivo::findOrFail($datos['motivo_id']);

            $personal = Personal::findOrFail($datos['personal_id']);

            $retencion = ArmaRetencion::create([
                'personal_id' => $personal->id,
                'tipo' => $motivo->tipo_asignado,
                'motivo_id' => $motivo->id,
                'fecha_posesion' => $datos['fecha_posesion'],
                'dias_restantes' => null,
                'fecha_elevacion' => $datos['fecha_elevacion'] ?? null,
                'fecha_devolucion' => $datos['fecha_devolucion'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'EN_ARMERIA',
                'created_by' => Auth::id(),
            ]);

            $retencion->dias_restantes = $this->calcularDiasRestantes($retencion);
            $retencion->estado = $this->calcularEstado($retencion);
            $retencion->save();

            $this->auditar($retencion, 'CREAR', 'Registro creado: Funcionario ' . $personal->nombre_completo . ' - Arma ' . $personal->numeracion_arma);

            DB::commit();

            return $retencion;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear retención de arma: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar un registro existente.
     */
    public function actualizar(ArmaRetencion $retencion, array $datos): ArmaRetencion
    {
        DB::beginTransaction();

        try {
            $cambios = [];

            if (isset($datos['motivo_id']) && $datos['motivo_id'] != $retencion->motivo_id) {
                $motivo = ArmaMotivo::findOrFail($datos['motivo_id']);
                $cambios[] = 'Motivo: ' . $retencion->motivo->nombre . ' → ' . $motivo->nombre;
                $retencion->motivo_id = $motivo->id;
                $retencion->tipo = $motivo->tipo_asignado;
            }

            if (isset($datos['personal_id']) && $datos['personal_id'] != $retencion->personal_id) {
                $cambios[] = 'Funcionario: ' . $retencion->personal->nombre_completo . ' → ' . $retencion->personal->nombre_completo;
                $retencion->personal_id = $datos['personal_id'];
            }

            if (isset($datos['fecha_posesion']) && $datos['fecha_posesion'] !== $retencion->fecha_posesion->format('Y-m-d')) {
                $cambios[] = 'Fecha posesión: ' . $retencion->fecha_posesion->format('d/m/Y') . ' → ' . Carbon::parse($datos['fecha_posesion'])->format('d/m/Y');
                $retencion->fecha_posesion = $datos['fecha_posesion'];
            }

            if (array_key_exists('observaciones', $datos) && $datos['observaciones'] !== $retencion->observaciones) {
                $cambios[] = 'Observaciones modificadas';
                $retencion->observaciones = $datos['observaciones'];
            }

            $retencion->dias_restantes = $this->calcularDiasRestantes($retencion);
            $retencion->estado = $this->calcularEstado($retencion);
            $retencion->updated_by = Auth::id();
            $retencion->save();

            if (!empty($cambios)) {
                $this->auditar($retencion, 'ACTUALIZAR', implode(', ', $cambios));
            }

            DB::commit();

            return $retencion;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar retención de arma: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar elevación del arma a Jefatura Central.
     */
    public function elevar(ArmaRetencion $retencion, ?string $fechaElevacion = null): ArmaRetencion
    {
        DB::beginTransaction();

        try {
            $fecha = $fechaElevacion ? Carbon::parse($fechaElevacion) : Carbon::today();

            $retencion->fecha_elevacion = $fecha;
            $retencion->estado = $this->calcularEstado($retencion);
            $retencion->updated_by = Auth::id();
            $retencion->save();

            $this->auditar($retencion, 'ELEVAR', 'Arma elevada a Jefatura Central en fecha ' . $fecha->format('d/m/Y'));

            DB::commit();

            return $retencion;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al elevar arma: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar devolución del arma al funcionario.
     */
    public function devolver(ArmaRetencion $retencion, ?string $fechaDevolucion = null): ArmaRetencion
    {
        DB::beginTransaction();

        try {
            $fecha = $fechaDevolucion ? Carbon::parse($fechaDevolucion) : Carbon::today();

            $retencion->fecha_devolucion = $fecha;
            $retencion->estado = $this->calcularEstado($retencion);
            $retencion->updated_by = Auth::id();
            $retencion->save();

            $this->auditar($retencion, 'DEVOLVER', 'Arma devuelta a funcionario en fecha ' . $fecha->format('d/m/Y'));

            DB::commit();

            return $retencion;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al devolver arma: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar (soft delete) un registro.
     */
    public function eliminar(ArmaRetencion $retencion): void
    {
        DB::beginTransaction();

        try {
            $this->auditar($retencion, 'ELIMINAR', 'Registro eliminado: Funcionario ' . $retencion->personal->nombre_completo . ' - Arma ' . $retencion->personal->numeracion_arma);

            $retencion->updated_by = Auth::id();
            $retencion->save();
            $retencion->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar retención de arma: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar auditoría del cambio.
     */
    private function auditar(ArmaRetencion $retencion, string $accion, string $cambios): void
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'nombre_tabla' => 'arma_retenciones',
            'accion' => $accion,
            'cambios' => json_encode([
                'id' => $retencion->id,
                'personal_id' => $retencion->personal_id,
                'numeracion_arma' => $retencion->personal->numeracion_arma,
                'detalle' => $cambios,
            ]),
        ]);
    }
}
