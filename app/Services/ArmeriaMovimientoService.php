<?php

namespace App\Services;

use App\Models\ArmeriaAdjunto;
use App\Models\ArmeriaArma;
use App\Models\ArmeriaChaleco;
use App\Models\ArmeriaMovimiento;
use App\Models\Auditoria;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Gestiona el ciclo de vida (alta, cambios de estado, movimientos entre
 * armerías y adjuntos) tanto de ArmeriaArma como de ArmeriaChaleco, dado
 * que ambos modelos comparten exactamente las mismas reglas de negocio.
 */
class ArmeriaMovimientoService
{
    public function crear(string $modelo, array $datos): Model
    {
        DB::beginTransaction();

        try {
            /** @var ArmeriaArma|ArmeriaChaleco $item */
            $item = $modelo::create([
                ...$datos,
                'estado' => $datos['estado'] ?? 'EN_SERVICIO',
                'ubicacion' => $datos['ubicacion'] ?? 'DIVISION_911',
                'created_by' => Auth::id(),
            ]);

            $this->registrarMovimiento($item, 'CARGA', comentario: $datos['comentario'] ?? null, fecha: $datos['fecha'] ?? null);
            $this->auditar($item, 'CREAR', 'Registro creado: N° serie ' . $item->numero_serie);

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear registro de armería: ' . $e->getMessage());
            throw $e;
        }
    }

    public function actualizar(Model $item, array $datos): Model
    {
        DB::beginTransaction();

        try {
            $item->fill(collect($datos)->except(['comentario', 'estado', 'ubicacion'])->all());
            $item->updated_by = Auth::id();
            $item->save();

            $this->registrarMovimiento($item, 'MODIFICAR', comentario: $datos['comentario'] ?? null);
            $this->auditar($item, 'ACTUALIZAR', 'Datos modificados: N° serie ' . $item->numero_serie);

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar registro de armería: ' . $e->getMessage());
            throw $e;
        }
    }

    public function cambiarEstado(Model $item, string $nuevoEstado, ?string $comentario = null, ?string $fecha = null): Model
    {
        DB::beginTransaction();

        try {
            $estadoAnterior = $item->estado;
            $item->estado = $nuevoEstado;
            $item->updated_by = Auth::id();
            $item->save();

            $this->registrarMovimiento($item, 'CAMBIO_ESTADO', estadoAnterior: $estadoAnterior, estadoNuevo: $nuevoEstado, comentario: $comentario, fecha: $fecha);
            $this->auditar($item, 'CAMBIO_ESTADO', "Estado: {$estadoAnterior} → {$nuevoEstado}");

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al cambiar estado en armería: ' . $e->getMessage());
            throw $e;
        }
    }

    public function enviarAJefaturaCentral(Model $item, ?string $comentario = null, ?string $fecha = null): Model
    {
        return $this->moverUbicacion($item, 'JEFATURA_CENTRAL', 'ENVIO_JEFATURA', $comentario, $fecha);
    }

    public function retornarADivision911(Model $item, ?string $comentario = null, ?string $fecha = null): Model
    {
        return $this->moverUbicacion($item, 'DIVISION_911', 'RETORNO_DIVISION', $comentario, $fecha);
    }

    private function moverUbicacion(Model $item, string $nuevaUbicacion, string $accion, ?string $comentario, ?string $fecha = null): Model
    {
        DB::beginTransaction();

        try {
            $ubicacionAnterior = $item->ubicacion;
            $item->ubicacion = $nuevaUbicacion;
            $item->updated_by = Auth::id();
            $item->save();

            $this->registrarMovimiento($item, $accion, ubicacionAnterior: $ubicacionAnterior, ubicacionNueva: $nuevaUbicacion, comentario: $comentario, fecha: $fecha);
            $this->auditar($item, $accion, "Ubicación: {$ubicacionAnterior} → {$nuevaUbicacion}");

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al mover ubicación en armería: ' . $e->getMessage());
            throw $e;
        }
    }

    public function agregarComentario(Model $item, string $comentario, ?string $fecha = null): void
    {
        $this->registrarMovimiento($item, 'COMENTARIO', comentario: $comentario, fecha: $fecha);
    }

    public function eliminar(Model $item, string $motivo): void
    {
        DB::beginTransaction();

        try {
            $this->registrarMovimiento($item, 'ELIMINAR', comentario: $motivo);
            $this->auditar($item, 'ELIMINAR', 'Registro eliminado por ' . (Auth::user()->name ?? 'sistema') . '. Motivo: ' . $motivo);

            $item->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar registro de armería: ' . $e->getMessage());
            throw $e;
        }
    }

    public function adjuntar(Model $item, UploadedFile $archivo): ArmeriaAdjunto
    {
        $ruta = $archivo->store('armeria', 'anexos');

        return $item->adjuntos()->create([
            'tipo' => str_starts_with($archivo->getMimeType(), 'image/') ? 'IMAGEN' : 'DOCUMENTO',
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'user_id' => Auth::id(),
        ]);
    }

    public function eliminarAdjunto(ArmeriaAdjunto $adjunto): void
    {
        if (Storage::disk('anexos')->exists($adjunto->ruta)) {
            Storage::disk('anexos')->delete($adjunto->ruta);
        }

        $adjunto->delete();
    }

    private function registrarMovimiento(
        Model $item,
        string $accion,
        ?string $ubicacionAnterior = null,
        ?string $ubicacionNueva = null,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?string $comentario = null,
        ?string $fecha = null,
    ): void {
        ArmeriaMovimiento::create([
            'movable_type' => get_class($item),
            'movable_id' => $item->id,
            'accion' => $accion,
            'ubicacion_anterior' => $ubicacionAnterior,
            'ubicacion_nueva' => $ubicacionNueva,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'user_id' => Auth::id(),
            'comentario' => $comentario,
            'created_at' => $this->resolverFecha($fecha),
        ]);
    }

    /**
     * Permite registrar movimientos atrasados (cargados un día después de
     * haber ocurrido realmente), con la fecha y hora reales del hecho en
     * lugar del momento en que se carga en el sistema. $fecha llega como un
     * único valor fecha+hora (input datetime-local).
     */
    private function resolverFecha(?string $fecha): Carbon
    {
        return $fecha ? Carbon::parse($fecha) : now();
    }

    private function auditar(Model $item, string $accion, string $detalle): void
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'nombre_tabla' => $item->getTable(),
            'accion' => $accion,
            'cambios' => json_encode([
                'id' => $item->id,
                'numero_serie' => $item->numero_serie,
                'detalle' => $detalle,
            ]),
        ]);
    }
}
