<?php

namespace App\Jobs;

use App\Models\Importacion;
use App\Services\EventoCecocoParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarArchivoEventoCecoco implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;
    public $backoff = 60;

    protected $archivoPath;
    protected $nombreOriginal;
    protected $importacionId;

    public function __construct(string $archivoPath, string $nombreOriginal, ?int $importacionId = null)
    {
        $this->archivoPath = $archivoPath;
        $this->nombreOriginal = $nombreOriginal;
        $this->importacionId = $importacionId;
    }

    public function handle(EventoCecocoParser $parser)
    {
        try {
            Log::info("Iniciando procesamiento de archivo: {$this->nombreOriginal}", [
                'archivo_path' => $this->archivoPath,
                'importacion_id' => $this->importacionId,
            ]);

            $importacion = null;

            if ($this->importacionId) {
                $importacion = Importacion::find($this->importacionId);
                if ($importacion) {
                    $importacion->update(['estado' => 'procesando']);
                }
            }

            if (!$importacion) {
                $importacion = Importacion::create([
                    'nombre_archivo' => $this->nombreOriginal,
                    'estado' => 'procesando',
                ]);
            }

            if (!Storage::disk('local')->exists($this->archivoPath)) {
                $fullPath = Storage::disk('local')->path($this->archivoPath);
                Log::error("Archivo temporal no encontrado", [
                    'archivo_path' => $this->archivoPath,
                    'full_path' => $fullPath,
                    'storage_path' => storage_path('app'),
                    'exists_check' => file_exists($fullPath),
                ]);
                throw new \RuntimeException("Archivo temporal no encontrado: {$this->archivoPath}");
            }

            $archivoTemporal = Storage::disk('local')->path($this->archivoPath);
            
            Log::info("Archivo temporal encontrado", [
                'path' => $archivoTemporal,
                'size' => filesize($archivoTemporal),
            ]);

            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $archivoTemporal,
                $this->nombreOriginal,
                mime_content_type($archivoTemporal),
                null,
                true
            );

            $inicio = microtime(true);
            $filas = $this->leerFilas($uploadedFile, $parser);
            $resultado = $this->persistir($filas, $importacion, $parser);
            $tiempoProcesamiento = (int)(microtime(true) - $inicio);

            $importacion->update([
                'periodo' => $resultado['periodo'],
                'anio' => $resultado['anio'],
                'mes' => $resultado['mes'],
                'total_registros' => $resultado['total'],
                'registros_importados' => $resultado['importados'],
                'registros_duplicados' => $resultado['duplicados'],
                'registros_omitidos' => $resultado['omitidos'],
                'errores' => !empty($resultado['errores']) ? implode("\n", $resultado['errores']) : null,
                'tiempo_procesamiento' => $tiempoProcesamiento,
                'estado' => 'completado',
            ]);

            if ($importacion->anio) {
                Cache::forget('cecoco_meses_' . $importacion->anio);
            }

            Cache::forget('cecoco_anios');
            Cache::forget('cecoco_tipos');
            Cache::forget('cecoco_operadores');
            Cache::forget('cecoco_total_bd');
            Cache::forget('cecoco_total_importaciones');
            Cache::forget('cecoco_total_archivos_importados');
            Cache::forget('cecoco_importaciones_por_anio');

            if (Storage::disk('local')->exists($this->archivoPath)) {
                Storage::disk('local')->delete($this->archivoPath);
            }

            Log::info("Archivo procesado exitosamente: {$this->nombreOriginal}", [
                'importacion_id' => $importacion->id,
                'registros_importados' => $resultado['importados'],
            ]);

        } catch (\Exception $e) {
            if (isset($importacion)) {
                $importacion->update([
                    'estado' => 'error',
                    'errores' => $e->getMessage(),
                ]);
            }
            Log::error("Error procesando archivo: {$this->nombreOriginal}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function leerFilas($archivo, EventoCecocoParser $parser)
    {
        $reflection = new \ReflectionClass($parser);
        $method = $reflection->getMethod('leerFilas');
        $method->setAccessible(true);
        return $method->invoke($parser, $archivo);
    }

    private function persistir(array $filas, Importacion $importacion, EventoCecocoParser $parser)
    {
        $reflection = new \ReflectionClass($parser);
        $method = $reflection->getMethod('persistir');
        $method->setAccessible(true);
        return $method->invoke($parser, $filas, $importacion);
    }

    public function failed(\Throwable $exception)
    {
        if ($this->importacionId) {
            $importacion = Importacion::find($this->importacionId);
            if ($importacion) {
                $importacion->update([
                    'estado' => 'error',
                    'errores' => "Job falló después de {$this->tries} intentos: " . $exception->getMessage(),
                ]);
            }
        }

        Log::error("Job falló definitivamente: {$this->nombreOriginal}", [
            'error' => $exception->getMessage(),
            'archivo_path' => $this->archivoPath,
            'importacion_id' => $this->importacionId,
        ]);
    }
}
