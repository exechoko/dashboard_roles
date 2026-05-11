<?php

namespace App\Console\Commands;

use App\Jobs\ProcesarArchivoEventoCecoco;
use App\Models\Importacion;
use App\Services\CecocoEventosReporteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportarEventosDiaAnterior extends Command
{
    protected $signature = 'cecoco:importar-dia-anterior
                            {--fecha= : Fecha a importar en formato Y-m-d (default: ayer)}
                            {--dry-run : Descarga el archivo pero no lo importa}';

    protected $description = 'Descarga automáticamente el reporte XLS del día anterior desde CECOCO e importa los eventos';

    public function handle(CecocoEventosReporteService $reporteService): int
    {
        $fecha = $this->option('fecha')
            ? \Carbon\Carbon::parse($this->option('fecha'))
            : now()->subDay();

        $nombreArchivo = 'reporte_' . $fecha->format('Y_m_d') . '.xls';

        $ahora = now()->format('Y-m-d H:i:s');
        $this->line("========================================");
        $this->line("[{$ahora}] cecoco:importar-dia-anterior iniciado");
        $this->info("[{$ahora}] Importando eventos del {$fecha->format('d/m/Y')}...");
        Log::info('cecoco:importar-dia-anterior iniciado', ['fecha' => $fecha->toDateString()]);

        try {
            $this->line('[' . now()->format('Y-m-d H:i:s') . '] Descargando Excel...');
            $contenido = $reporteService->descargar($fecha);
        } catch (\Exception $e) {
            $this->error('[' . now()->format('Y-m-d H:i:s') . '] Error descargando Excel: ' . $e->getMessage());
            Log::error('cecoco:importar-dia-anterior - error descarga', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        // Verificar que es un XLS real (magic bytes D0 CF 11 E0 para OLE2 / Compound Document)
        if (substr($contenido, 0, 4) !== "\xD0\xCF\x11\xE0") {
            // Podría ser HTML de error; loguear primero 500 chars
            Log::warning('cecoco:importar-dia-anterior - respuesta no parece XLS', [
                'preview' => substr($contenido, 0, 500),
            ]);
            $this->warn('[' . now()->format('Y-m-d H:i:s') . '] Advertencia: la respuesta no parece un archivo XLS válido. Continuando de todas formas...');
        }

        if ($this->option('dry-run')) {
            $bytes = strlen($contenido);
            $this->info("Dry-run: archivo de {$bytes} bytes descargado. No se importa.");
            return self::SUCCESS;
        }

        // 4) Guardar en storage temporal y disparar job
        $rutaTemporal = 'importaciones_temp/' . $nombreArchivo;
        Storage::disk('local')->put($rutaTemporal, $contenido);

        $bytes = strlen($contenido);
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Archivo guardado (' . $bytes . ' bytes). Encolando procesamiento...');

        $importacion = Importacion::create([
            'nombre_archivo' => $nombreArchivo,
            'estado'         => 'pendiente',
        ]);

        ProcesarArchivoEventoCecoco::dispatch($rutaTemporal, $nombreArchivo, $importacion->id, true);

        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Job despachado. Importacion ID: ' . $importacion->id);
        $this->line("========================================");
        Log::info('cecoco:importar-dia-anterior - job despachado', [
            'importacion_id' => $importacion->id,
            'archivo'        => $nombreArchivo,
            'bytes'          => $bytes,
        ]);

        return self::SUCCESS;
    }
}
