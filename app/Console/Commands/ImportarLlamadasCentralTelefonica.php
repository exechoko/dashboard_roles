<?php

namespace App\Console\Commands;

use App\Services\LlamadaCentralTelefonicaImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarLlamadasCentralTelefonica extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cecoco:importar-llamadas-central-telefonica {--dir=docs/varios/CSVs_central_telefonica : Carpeta con los CSV de la central telefonica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa (o actualiza) los CDR de la central telefonica del CeCoCo desde los CSV exportados';

    public function handle(LlamadaCentralTelefonicaImportService $service): int
    {
        DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1024M');

        $dir = base_path($this->option('dir'));

        if (!is_dir($dir)) {
            $this->error("No existe la carpeta: {$dir}");

            return self::FAILURE;
        }

        $archivos = glob($dir . DIRECTORY_SEPARATOR . '*.csv');

        if (empty($archivos)) {
            $this->warn("No se encontraron CSV en: {$dir}");

            return self::SUCCESS;
        }

        sort($archivos);

        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            $this->info("Procesando {$nombre}...");

            $resultado = $service->importarArchivo($archivo, $nombre);

            $this->line("  {$resultado['total']} llamadas procesadas" . ($resultado['omitidos'] > 0 ? ", {$resultado['omitidos']} filas omitidas" : ''));

            gc_collect_cycles();
        }

        $this->info('Importacion finalizada.');

        return self::SUCCESS;
    }
}
