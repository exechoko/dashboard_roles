<?php

namespace App\Services;

use App\Models\AlertaMovimiento;
use App\Models\Auditoria;
use App\Models\DominioAlerta;
use App\Models\PersonaAlerta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Gestiona el ciclo de vida (alta, edición, cambio de activo/inactivo y
 * eliminación) tanto de DominioAlerta como de PersonaAlerta, dado que ambos
 * modelos comparten exactamente las mismas reglas de negocio de registro
 * de novedades para el sistema de video (LPR / reconocimiento facial).
 */
class AlertaVideoService
{
    public function crear(string $modelo, array $datos, ?UploadedFile $foto = null): Model
    {
        DB::beginTransaction();

        try {
            $datos['activo'] = $datos['activo'] ?? true;
            $datos['created_by'] = Auth::id();
            $datos['fecha_carga'] = $datos['fecha_carga'] ?? now()->toDateString();

            if ($foto instanceof UploadedFile) {
                $datos['foto'] = $this->guardarFoto($foto);
            }

            $comentario = $datos['comentario'] ?? null;
            unset($datos['comentario']);

            /** @var DominioAlerta|PersonaAlerta $item */
            $item = $modelo::create($datos);

            $this->registrarMovimiento($item, 'CARGA', comentario: $comentario);
            $this->auditar($item, 'CREAR', 'Registro creado: ' . $this->identificador($item));

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear registro de alerta de video: ' . $e->getMessage());
            throw $e;
        }
    }

    public function actualizar(Model $item, array $datos, ?UploadedFile $foto = null): Model
    {
        DB::beginTransaction();

        try {
            $comentario = $datos['comentario'] ?? null;
            unset($datos['comentario'], $datos['activo']);

            if ($foto instanceof UploadedFile) {
                $this->eliminarFotoAnterior($item);
                $datos['foto'] = $this->guardarFoto($foto);
            }

            $item->fill($datos);
            $item->updated_by = Auth::id();
            $item->save();

            $this->registrarMovimiento($item, 'MODIFICAR', comentario: $comentario);
            $this->auditar($item, 'ACTUALIZAR', 'Datos modificados: ' . $this->identificador($item));

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar registro de alerta de video: ' . $e->getMessage());
            throw $e;
        }
    }

    public function cambiarActivo(Model $item, bool $nuevoActivo, ?string $comentario = null): Model
    {
        DB::beginTransaction();

        try {
            $anterior = (bool) $item->activo;
            $item->activo = $nuevoActivo;
            $item->updated_by = Auth::id();
            $item->save();

            $this->registrarMovimiento(
                $item,
                'CAMBIO_ACTIVO',
                estadoAnterior: $anterior ? 'ACTIVO' : 'INACTIVO',
                estadoNuevo: $nuevoActivo ? 'ACTIVO' : 'INACTIVO',
                comentario: $comentario,
            );
            $this->auditar($item, 'CAMBIO_ACTIVO', 'Activo: ' . ($anterior ? 'SI' : 'NO') . ' → ' . ($nuevoActivo ? 'SI' : 'NO'));

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al cambiar el estado activo de la alerta: ' . $e->getMessage());
            throw $e;
        }
    }

    public function agregarComentario(Model $item, string $comentario): void
    {
        $this->registrarMovimiento($item, 'COMENTARIO', comentario: $comentario);
    }

    public function eliminar(Model $item, string $motivo): void
    {
        DB::beginTransaction();

        try {
            $this->registrarMovimiento($item, 'ELIMINAR', comentario: $motivo);
            $this->auditar($item, 'ELIMINAR', 'Registro eliminado. Motivo: ' . $motivo);

            $item->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar registro de alerta de video: ' . $e->getMessage());
            throw $e;
        }
    }

    private function guardarFoto(UploadedFile $foto): string
    {
        return $foto->store('alertas-video/personas', 'anexos');
    }

    private function eliminarFotoAnterior(Model $item): void
    {
        if ($item instanceof PersonaAlerta && $item->foto && Storage::disk('anexos')->exists($item->foto)) {
            Storage::disk('anexos')->delete($item->foto);
        }
    }

    private function identificador(Model $item): string
    {
        return $item instanceof DominioAlerta ? (string) $item->dominio : (string) $item->apellido_nombre;
    }

    private function registrarMovimiento(
        Model $item,
        string $accion,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?string $comentario = null,
    ): void {
        AlertaMovimiento::create([
            'movable_type' => get_class($item),
            'movable_id' => $item->id,
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'user_id' => Auth::id(),
            'comentario' => $comentario,
            'created_at' => now(),
        ]);
    }

    private function auditar(Model $item, string $accion, string $detalle): void
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'nombre_tabla' => $item->getTable(),
            'accion' => $accion,
            'cambios' => json_encode([
                'id' => $item->id,
                'identificador' => $this->identificador($item),
                'detalle' => $detalle,
            ]),
        ]);
    }
}
