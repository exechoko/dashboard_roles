<?php

namespace App\Console\Commands;

use App\Models\MailArchivo;
use App\Services\Mbox\MboxIndexador;
use Illuminate\Console\Command;

class IndexarMbox extends Command
{
    protected $signature = 'mbox:indexar {archivo_id : ID de mail_archivos a indexar}
        {--reiniciar : Ignora el progreso guardado y vuelve a indexar desde el principio}';

    protected $description = 'Indexa en primer plano un .mbox puntual (útil en dev, para diagnosticar, o para reanudar a mano)';

    public function handle(MboxIndexador $indexador): int
    {
        $archivo = MailArchivo::find((int) $this->argument('archivo_id'));

        if (!$archivo) {
            $this->error('No existe ese archivo registrado (mail_archivos).');

            return self::FAILURE;
        }

        $reiniciar = (bool) $this->option('reiniciar');

        $this->info("Indexando: {$archivo->nombre_archivo} (buzón: {$archivo->buzon->nombre})");
        $this->info('Tamaño: '.number_format($archivo->tamano_bytes / 1048576, 1).' MB');

        if (!file_exists($archivo->ruta_absoluta)) {
            $this->error("No se encuentra el archivo en disco: {$archivo->ruta_absoluta}");

            return self::FAILURE;
        }

        $archivo->update(['estado' => 'indexando', 'error_message' => null]);

        $inicio = microtime(true);

        try {
            $resultado = $indexador->indexar($archivo, $reiniciar);
        } catch (\Throwable $e) {
            $archivo->update(['estado' => 'error', 'error_message' => $e->getMessage()]);
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }

        $archivo->update([
            'estado' => 'indexado',
            'mensajes_total' => $resultado['mensajes_total'],
            'mensajes_nuevos' => $resultado['mensajes_nuevos'],
            'indexado_at' => now(),
        ]);

        $segundos = round(microtime(true) - $inicio, 1);
        $this->info("Listo en {$segundos}s. Mensajes totales: {$resultado['mensajes_total']}, nuevos: {$resultado['mensajes_nuevos']}.");

        return self::SUCCESS;
    }
}
