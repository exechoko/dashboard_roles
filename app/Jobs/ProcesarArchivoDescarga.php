<?php

namespace App\Jobs;

use App\Models\DescargaArchivo;
use App\Services\Descargas\DescargaVideoTranscoder;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarArchivoDescarga implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout;
    public $tries;
    public $backoff;

    public function __construct(
        protected string $archivoTemporalPath,
        protected string $nombreOriginal,
        protected int $categoriaId,
        protected ?string $descripcion,
        protected array $rolesIds,
        protected array $usuariosIds,
        protected ?string $expiraAt,
        protected bool $destacado,
        protected int $userId,
        protected bool $notificar = true
    ) {
        $this->timeout = config('descargas.job_timeout', 7200);
        $this->tries = config('descargas.job_tries', 2);
        $this->backoff = config('descargas.job_backoff', 300);
        // Cola dedicada con retry_after largo (ver config/queue.php): la de
        // 'database' (90s) re-entregaría este job a otro worker antes de que
        // termine de mover un archivo pesado. Requiere un worker propio en
        // producción, igual que 'mbox'/'backups': php artisan queue:work descargas --queue=descargas
        $this->onConnection('descargas')->onQueue('descargas');
    }

    public function handle(): void
    {
        $archivo = null;

        try {
            Log::info('Iniciando procesamiento de archivo de descarga', [
                'archivo_temporal' => $this->archivoTemporalPath,
                'nombre_original' => $this->nombreOriginal,
                'user_id' => $this->userId,
            ]);

            // Crear registro inicial en BD
            $archivo = DescargaArchivo::create([
                'categoria_id' => $this->categoriaId,
                'nombre_original' => $this->nombreOriginal,
                'nombre_archivo' => $this->nombreOriginal,
                'ruta_relativa' => '', // Se actualizará después
                // Resuelto contra el disco 'descargas' (no storage_path('app/...')):
                // el temporal vive bajo DESCARGAS_PATH, no bajo el disco por defecto.
                'mime_type' => Storage::disk('descargas')->mimeType($this->archivoTemporalPath),
                'extension' => pathinfo($this->nombreOriginal, PATHINFO_EXTENSION),
                'tamano_bytes' => 0, // Se actualizará después
                'descripcion' => $this->descripcion,
                'destacado' => $this->destacado,
                'user_id' => $this->userId,
                'expira_at' => $this->expiraAt,
                'activo' => true,
                'estado_proceso' => 'procesando',
                'progreso' => 10,
            ]);

            // Asignar roles
            if (!empty($this->rolesIds)) {
                $archivo->roles()->sync($this->rolesIds);
                $archivo->update(['progreso' => 20]);
            }

            // Asignar usuarios específicos
            if (!empty($this->usuariosIds)) {
                $archivo->usuarios()->sync($this->usuariosIds);
                $archivo->update(['progreso' => 30]);
            }

            // Mover archivo a ubicación final
            $anio = date('Y');
            $mes = date('m');
            $nombreUnico = uniqid() . '_' . $this->nombreOriginal;
            $rutaFinal = "{$anio}/{$mes}/{$nombreUnico}";

            Storage::disk('descargas')->move($this->archivoTemporalPath, $rutaFinal);

            $archivo->update([
                'nombre_archivo' => $nombreUnico,
                'ruta_relativa' => $rutaFinal,
                'tamano_bytes' => Storage::disk('descargas')->size($rutaFinal),
                'progreso' => 70,
            ]);

            $this->transcodificarSiHaceFalta($archivo);

            // Enviar notificaciones (opcional, para no llenar de mails
            // cuando se comparte con un rol/usuario que no hace falta avisar)
            if ($this->notificar) {
                EnviarNotificacionDescarga::dispatch($archivo);
            }
            $archivo->update(['progreso' => 90]);

            // Completar
            $archivo->update([
                'estado_proceso' => 'completado',
                'progreso' => 100,
                'procesado_at' => now(),
            ]);

            Log::info('Archivo de descarga procesado exitosamente', [
                'archivo_id' => $archivo->id,
                'nombre_original' => $this->nombreOriginal,
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando archivo de descarga', [
                'error' => $e->getMessage(),
                'archivo_temporal' => $this->archivoTemporalPath,
                'nombre_original' => $this->nombreOriginal,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($archivo) {
                $archivo->update([
                    'estado_proceso' => 'error',
                    'error_proceso' => $e->getMessage(),
                ]);
            }

            // Notificar por Telegram
            try {
                $telegram = app(TelegramService::class);
                $telegram->enviarMensaje(
                    "❌ Error en Job ProcesarArchivoDescarga\n\n" .
                    "Archivo: {$this->nombreOriginal}\n" .
                    "Error: {$e->getMessage()}\n" .
                    "User ID: {$this->userId}"
                );
            } catch (\Exception $telegramError) {
                Log::error('Error enviando notificación Telegram', [
                    'error' => $telegramError->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Si el archivo es un video en un códec que los navegadores no
     * reproducen nativamente (ej. HEVC de celulares), lo transcodifica a
     * H.264/AAC para que la vista previa de la ficha funcione. Si el
     * conversor no está disponible o la conversión falla, se deja el
     * archivo original tal cual — solo se pierde el preview inline, la
     * descarga no se ve afectada.
     */
    private function transcodificarSiHaceFalta(DescargaArchivo $archivo): void
    {
        if (!in_array(strtolower($archivo->extension), config('descargas.extensiones_video', []), true)) {
            return;
        }

        $rutaAbsolutaOriginal = Storage::disk('descargas')->path($archivo->ruta_relativa);
        $transcoder = app(DescargaVideoTranscoder::class);

        if (!$transcoder->haceFaltaTranscodificar($rutaAbsolutaOriginal)) {
            return;
        }

        Log::info('Transcodificando video a H.264/AAC para compatibilidad de navegador', [
            'archivo_id' => $archivo->id,
            'ruta' => $archivo->ruta_relativa,
        ]);

        $tmpSalida = $transcoder->transcodificar($rutaAbsolutaOriginal);

        if ($tmpSalida === null) {
            return;
        }

        $directorio = pathinfo($archivo->ruta_relativa, PATHINFO_DIRNAME);
        $nuevoNombreArchivo = pathinfo($archivo->nombre_archivo, PATHINFO_FILENAME) . '.mp4';
        $nuevaRutaRelativa = $directorio . '/' . $nuevoNombreArchivo;
        $nuevaRutaAbsoluta = Storage::disk('descargas')->path($nuevaRutaRelativa);

        if (file_exists($nuevaRutaAbsoluta)) {
            @unlink($nuevaRutaAbsoluta);
        }

        if (!rename($tmpSalida, $nuevaRutaAbsoluta)) {
            Log::warning('DescargaVideoTranscoder: no se pudo mover el video transcodificado a su ubicación final', [
                'archivo_id' => $archivo->id,
            ]);
            @unlink($tmpSalida);

            return;
        }

        if ($rutaAbsolutaOriginal !== $nuevaRutaAbsoluta && file_exists($rutaAbsolutaOriginal)) {
            @unlink($rutaAbsolutaOriginal);
        }

        $archivo->update([
            'ruta_relativa' => $nuevaRutaRelativa,
            'nombre_archivo' => $nuevoNombreArchivo,
            'nombre_original' => pathinfo($archivo->nombre_original, PATHINFO_FILENAME) . '.mp4',
            'extension' => 'mp4',
            'mime_type' => 'video/mp4',
            'tamano_bytes' => filesize($nuevaRutaAbsoluta),
        ]);

        Log::info('Video transcodificado exitosamente', ['archivo_id' => $archivo->id]);
    }

    public function failed(\Exception $e): void
    {
        Log::error('Job ProcesarArchivoDescarga falló definitivamente', [
            'error' => $e->getMessage(),
            'archivo_temporal' => $this->archivoTemporalPath,
            'nombre_original' => $this->nombreOriginal,
        ]);

        // Notificar por Telegram
        try {
            $telegram = app(TelegramService::class);
            $telegram->enviarMensaje(
                "🚨 Job ProcesarArchivoDescarga FALLÓ\n\n" .
                "Archivo: {$this->nombreOriginal}\n" .
                "Error: {$e->getMessage()}\n" .
                "User ID: {$this->userId}\n" .
                "Intentos: {$this->tries}"
            );
        } catch (\Exception $telegramError) {
            Log::error('Error enviando notificación Telegram', [
                'error' => $telegramError->getMessage(),
            ]);
        }
    }
}
