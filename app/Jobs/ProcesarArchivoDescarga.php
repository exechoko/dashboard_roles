<?php

namespace App\Jobs;

use App\Models\DescargaArchivo;
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
        protected int $userId
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
                'mime_type' => mime_content_type(storage_path('app/' . $this->archivoTemporalPath)),
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

            // Enviar notificaciones
            EnviarNotificacionDescarga::dispatch($archivo);
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
