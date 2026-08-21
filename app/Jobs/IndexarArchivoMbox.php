<?php

namespace App\Jobs;

use App\Models\MailArchivo;
use App\Services\Mbox\MboxIndexador;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Indexa un .mbox completo (puede ser de varios GB y tardar horas). Corre en
 * la cola dedicada 'mbox' (ver config/queue.php), separada de 'default' para
 * no frenar el resto de los jobs del sistema, y con retry_after alto para
 * que el worker no lo re-entregue en paralelo mientras sigue corriendo.
 */
class IndexarArchivoMbox implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;
    public $tries = 1;
    public $uniqueFor = 86400;

    public function __construct(
        public int $archivoId,
        public bool $reiniciar = false
    ) {
        // No se redeclaran $connection/$queue como propiedades de la clase: el trait
        // Queueable ya las declara, y según la versión de illuminate/bus instalada
        // puede tiparlas (public ?string $connection = null;) — si la clase las
        // vuelve a declarar con un tipo distinto (o sin tipo), PHP tira un fatal
        // de "propiedad incompatible" al componer el trait. Asignarlas acá evita
        // el choque de declaraciones sin importar cómo las tipe el trait.
        $this->connection = 'mbox';
        $this->queue = 'mbox';
    }

    public function uniqueId(): string
    {
        return "mbox-archivo-{$this->archivoId}";
    }

    public function handle(MboxIndexador $indexador): void
    {
        $archivo = MailArchivo::find($this->archivoId);
        if (!$archivo) {
            Log::channel('mbox')->warning('IndexarArchivoMbox: el archivo ya no existe.', ['archivo_id' => $this->archivoId]);

            return;
        }

        $archivo->update(['estado' => 'indexando', 'error_message' => null]);

        Log::channel('mbox')->info('Iniciando indexación de mbox.', [
            'archivo_id' => $archivo->id,
            'archivo' => $archivo->nombre_archivo,
            'buzon' => $archivo->buzon->nombre,
            'reiniciar' => $this->reiniciar,
        ]);

        try {
            $resultado = $indexador->indexar($archivo, $this->reiniciar);

            $archivo->update([
                'estado' => 'indexado',
                'mensajes_total' => $resultado['mensajes_total'],
                'mensajes_nuevos' => $resultado['mensajes_nuevos'],
                'indexado_at' => now(),
            ]);

            // Un .mbox grande inserta de golpe decenas de miles de filas: sin esto,
            // las estadísticas de InnoDB quedan desactualizadas hasta la próxima
            // vez que el motor decida refrescarlas solo, y mientras tanto el
            // optimizador puede elegir mal qué índice usar (búsquedas lentas en
            // el visor de correos justo después de indexar).
            if ($resultado['mensajes_nuevos'] > 0) {
                DB::statement('ANALYZE TABLE mail_mensajes');
            }

            Log::channel('mbox')->info('Indexación de mbox completada.', [
                'archivo_id' => $archivo->id,
                'mensajes_total' => $resultado['mensajes_total'],
                'mensajes_nuevos' => $resultado['mensajes_nuevos'],
            ]);
        } catch (Throwable $e) {
            $archivo->update([
                'estado' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('mbox')->error('Error indexando mbox.', [
                'archivo_id' => $archivo->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $archivo = MailArchivo::find($this->archivoId);
        $archivo?->update([
            'estado' => 'error',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
