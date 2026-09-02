<?php

namespace App\Jobs;

use App\Models\DescargaArchivo;
use App\Models\DescargaZipTemporal;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ComprimirArchivosZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout;
    public $tries;
    public $backoff;

    public function __construct(
        protected array $archivosIds,
        protected int $userId
    ) {
        $this->timeout = config('descargas.job_timeout', 7200);
        $this->tries = config('descargas.job_tries', 2);
        $this->backoff = config('descargas.job_backoff', 300);
        $this->onQueue('descargas');
    }

    public function handle(): void
    {
        $zipTemporal = null;

        try {
            Log::info('Iniciando compresión ZIP', [
                'archivos_ids' => $this->archivosIds,
                'user_id' => $this->userId,
            ]);

            // Obtener archivos
            $archivos = DescargaArchivo::whereIn('id', $this->archivosIds)->get();

            if ($archivos->isEmpty()) {
                throw new \Exception('No se encontraron archivos para comprimir');
            }

            // Validar tamaño total
            $tamanoTotal = $archivos->sum('tamano_bytes');
            $tamanoMaximoBytes = config('descargas.zip_tamano_maximo_gb', 10) * 1024 * 1024 * 1024;

            if ($tamanoTotal > $tamanoMaximoBytes) {
                throw new \Exception('El tamaño total de los archivos supera el límite de ' . 
                    config('descargas.zip_tamano_maximo_gb', 10) . ' GB');
            }

            // Crear directorio temporal para ZIPs
            $zipDir = 'descargas/zips_temporales';
            if (!Storage::exists($zipDir)) {
                Storage::makeDirectory($zipDir);
            }

            // Generar nombre único para el ZIP
            $token = Str::random(64);
            $zipNombre = 'zip_' . $token . '.zip';
            $zipRuta = $zipDir . '/' . $zipNombre;
            $zipRutaCompleta = storage_path('app/' . $zipRuta);

            // Crear ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipRutaCompleta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('No se pudo crear el archivo ZIP');
            }

            $totalArchivos = $archivos->count();
            $archivosProcesados = 0;

            foreach ($archivos as $archivo) {
                $archivoRuta = storage_path('app/' . $archivo->ruta_relativa);

                if (!file_exists($archivoRuta)) {
                    Log::warning('Archivo no encontrado, omitiendo', [
                        'archivo_id' => $archivo->id,
                        'ruta' => $archivoRuta,
                    ]);
                    continue;
                }

                $zip->addFile($archivoRuta, $archivo->nombre_original);
                $archivosProcesados++;

                // Actualizar progreso (30% a 90%)
                $progreso = 30 + (($archivosProcesados / $totalArchivos) * 60);
                
                // Crear registro temporal si no existe
                if (!$zipTemporal) {
                    $zipTemporal = DescargaZipTemporal::create([
                        'user_id' => $this->userId,
                        'token' => $token,
                        'ruta_zip' => $zipRuta,
                        'tamano_bytes' => 0,
                        'expira_at' => now()->addHours(config('descargas.zip_temp_expiracion_horas', 24)),
                        'descargado' => false,
                        'created_at' => now(),
                    ]);
                }
            }

            $zip->close();

            if ($archivosProcesados === 0) {
                throw new \Exception('No se pudo agregar ningún archivo al ZIP');
            }

            // Actualizar tamaño del ZIP
            $zipTamano = filesize($zipRutaCompleta);
            $zipTemporal->update([
                'tamano_bytes' => $zipTamano,
            ]);

            Log::info('ZIP creado exitosamente', [
                'zip_id' => $zipTemporal->id,
                'token' => $token,
                'tamano' => $zipTamano,
                'archivos_incluidos' => $archivosProcesados,
            ]);

        } catch (\Exception $e) {
            Log::error('Error comprimiendo ZIP', [
                'error' => $e->getMessage(),
                'archivos_ids' => $this->archivosIds,
                'user_id' => $this->userId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Limpiar ZIP si se creó
            if ($zipTemporal && Storage::exists($zipTemporal->ruta_zip)) {
                Storage::delete($zipTemporal->ruta_zip);
                $zipTemporal->delete();
            }

            // Notificar por Telegram
            try {
                $telegram = app(TelegramService::class);
                $telegram->enviarMensaje(
                    "❌ Error en Job ComprimirArchivosZip\n\n" .
                    "User ID: {$this->userId}\n" .
                    "Archivos: " . implode(', ', $this->archivosIds) . "\n" .
                    "Error: {$e->getMessage()}"
                );
            } catch (\Exception $telegramError) {
                Log::error('Error enviando notificación Telegram', [
                    'error' => $telegramError->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    public function failed(\Exception $e): void
    {
        Log::error('Job ComprimirArchivosZip falló definitivamente', [
            'error' => $e->getMessage(),
            'archivos_ids' => $this->archivosIds,
            'user_id' => $this->userId,
        ]);

        // Notificar por Telegram
        try {
            $telegram = app(TelegramService::class);
            $telegram->enviarMensaje(
                "🚨 Job ComprimirArchivosZip FALLÓ\n\n" .
                "User ID: {$this->userId}\n" .
                "Archivos: " . implode(', ', $this->archivosIds) . "\n" .
                "Error: {$e->getMessage()}\n" .
                "Intentos: {$this->tries}"
            );
        } catch (\Exception $telegramError) {
            Log::error('Error enviando notificación Telegram', [
                'error' => $telegramError->getMessage(),
            ]);
        }
    }
}
