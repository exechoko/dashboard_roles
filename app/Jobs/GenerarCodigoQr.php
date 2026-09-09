<?php

namespace App\Jobs;

use App\Models\DescargaArchivo;
use App\Models\DescargaQrCode;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerarCodigoQr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        protected int $archivoId,
        protected int $generadoPorId,
        protected ?int $expiraHoras = null,
        protected ?string $password = null
    ) {
        // Ver comentario en ProcesarArchivoDescarga sobre por qué usa la
        // conexión dedicada 'descargas' en vez de la 'database' por defecto.
        $this->onConnection('descargas')->onQueue('descargas');
    }

    public function handle(): void
    {
        try {
            Log::info('Iniciando generación de QR', [
                'archivo_id' => $this->archivoId,
                'generado_por' => $this->generadoPorId,
            ]);

            // Obtener archivo
            $archivo = DescargaArchivo::findOrFail($this->archivoId);

            // Crear directorio para QRs si no existe. Vive en el disco
            // 'descargas' (DESCARGAS_PATH), no en el disco por defecto.
            $qrDir = 'qrcodes';
            if (!Storage::disk('descargas')->exists($qrDir)) {
                Storage::disk('descargas')->makeDirectory($qrDir);
            }

            // Generar token único
            $token = Str::random(64);

            // Generar URL de descarga
            $urlDescarga = route('descargas.qr.descargar', $token);

            // Generar nombre único para el QR. SVG en vez de PNG: format('png')
            // requiere la extension imagick, que no esta instalada (y es
            // complicada de sumar en Windows Server) - ver DescargaAdminController::generarQr().
            $qrNombre = 'qr_' . $token . '.svg';
            $qrRuta = $qrDir . '/' . $qrNombre;
            $qrRutaCompleta = Storage::disk('descargas')->path($qrRuta);

            // Generar código QR
            $tamano = config('descargas.qr_tamano_px', 300);
            QrCode::format('svg')
                ->size($tamano)
                ->margin(1)
                ->generate($urlDescarga, $qrRutaCompleta);

            // Calcular expiración
            $expiraHoras = $this->expiraHoras ?? config('descargas.qr_default_expiracion_horas', 24);
            $expiraAt = now()->addHours($expiraHoras);

            // Crear registro en BD
            $qrCode = DescargaQrCode::create([
                'archivo_id' => $this->archivoId,
                'token' => $token,
                'ruta_qr' => $qrRuta,
                'password' => $this->password ? bcrypt($this->password) : null,
                'max_usos' => 1,
                'usos_count' => 0,
                'expira_at' => $expiraAt,
                'generado_por' => $this->generadoPorId,
                'activo' => true,
                'created_at' => now(),
            ]);

            Log::info('QR generado exitosamente', [
                'qr_id' => $qrCode->id,
                'token' => $token,
                'archivo_id' => $this->archivoId,
                'expira_at' => $expiraAt,
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando QR', [
                'error' => $e->getMessage(),
                'archivo_id' => $this->archivoId,
                'generado_por' => $this->generadoPorId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Notificar por Telegram
            try {
                $telegram = app(TelegramService::class);
                $telegram->enviarMensaje(
                    "❌ Error en Job GenerarCodigoQr\n\n" .
                    "Archivo ID: {$this->archivoId}\n" .
                    "Generado por: {$this->generadoPorId}\n" .
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
        Log::error('Job GenerarCodigoQr falló definitivamente', [
            'error' => $e->getMessage(),
            'archivo_id' => $this->archivoId,
            'generado_por' => $this->generadoPorId,
        ]);

        // Notificar por Telegram
        try {
            $telegram = app(TelegramService::class);
            $telegram->enviarMensaje(
                "🚨 Job GenerarCodigoQr FALLÓ\n\n" .
                "Archivo ID: {$this->archivoId}\n" .
                "Generado por: {$this->generadoPorId}\n" .
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
