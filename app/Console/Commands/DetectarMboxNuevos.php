<?php

namespace App\Console\Commands;

use App\Jobs\IndexarArchivoMbox;
use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Services\Mbox\MboxRepositorioArchivos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DetectarMboxNuevos extends Command
{
    protected $signature = 'mbox:detectar-nuevos';

    protected $description = 'Recorre las carpetas de los buzones activos, registra los .mbox nuevos o modificados y encola su indexación';

    public function handle(MboxRepositorioArchivos $repositorio): int
    {
        if (!config('mbox.auto_indexar')) {
            $this->info('MBOX_AUTO_INDEXAR está apagado; no se hace nada.');

            return self::SUCCESS;
        }

        $buzones = MailBuzon::where('activo', true)->get();
        $encolados = 0;

        foreach ($buzones as $buzon) {
            foreach ($repositorio->archivosDe($buzon) as $hallado) {
                $registro = $hallado['registro'];

                if ($registro === null) {
                    $registro = MailArchivo::create([
                        'buzon_id' => $buzon->id,
                        'nombre_archivo' => $hallado['nombre_archivo'],
                        'ruta_absoluta' => $hallado['ruta_absoluta'],
                        'tamano_bytes' => $hallado['tamano_bytes'],
                        'mtime_archivo' => $hallado['mtime'],
                        'estado' => 'pendiente',
                    ]);
                } elseif (!$hallado['requiere_reindexar']) {
                    continue;
                } else {
                    $registro->update([
                        'tamano_bytes' => $hallado['tamano_bytes'],
                        'mtime_archivo' => $hallado['mtime'],
                        'estado' => 'pendiente',
                    ]);
                }

                IndexarArchivoMbox::dispatch($registro->id);
                $encolados++;

                Log::channel('mbox')->info('mbox:detectar-nuevos encoló un archivo.', [
                    'buzon' => $buzon->nombre,
                    'archivo' => $registro->nombre_archivo,
                ]);
            }
        }

        $this->info("Archivos encolados para indexar: {$encolados}.");

        return self::SUCCESS;
    }
}
