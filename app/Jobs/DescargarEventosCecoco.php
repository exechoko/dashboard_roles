<?php

namespace App\Jobs;

use App\Models\Importacion;
use App\Services\CecocoEventosReporteService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DescargarEventosCecoco implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 2;
    public $backoff = 60;

    private string $fecha;
    private int $importacionId;
    private bool $actualizarPreliminares;

    public function __construct(string $fecha, int $importacionId, bool $actualizarPreliminares = true)
    {
        $this->fecha = $fecha;
        $this->importacionId = $importacionId;
        $this->actualizarPreliminares = $actualizarPreliminares;
    }

    public function handle(CecocoEventosReporteService $reporteService): void
    {
        $importacion = Importacion::findOrFail($this->importacionId);
        $importacion->update(['estado' => 'procesando']);

        try {
            $fecha = Carbon::parse($this->fecha);
            $contenido = $reporteService->descargar($fecha);
            $nombreArchivo = 'reporte_' . $fecha->format('Y_m_d') . '.xls';
            $rutaTemporal = 'importaciones_temp/' . $nombreArchivo;

            Storage::disk('local')->put($rutaTemporal, $contenido);

            $importacion->update([
                'nombre_archivo' => $nombreArchivo,
                'estado' => 'pendiente',
            ]);

            ProcesarArchivoEventoCecoco::dispatch(
                $rutaTemporal,
                $nombreArchivo,
                $importacion->id,
                $this->actualizarPreliminares
            );

            Log::info('Descarga manual CECOCO encoló procesamiento', [
                'importacion_id' => $importacion->id,
                'fecha' => $fecha->toDateString(),
                'bytes' => strlen($contenido),
            ]);
        } catch (\Throwable $e) {
            $importacion->update([
                'estado' => 'error',
                'errores' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $importacion = Importacion::find($this->importacionId);
        if ($importacion) {
            $importacion->update([
                'estado' => 'error',
                'errores' => 'Descarga falló: ' . $exception->getMessage(),
            ]);
        }
    }
}
