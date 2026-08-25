<?php

namespace App\Console\Commands;

use App\Models\ImportacionLlamadaCentralTelefonica;
use App\Services\CentralTelefonicaSincronizacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SincronizarLlamadasCentralTelefonica extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cecoco:sincronizar-llamadas-central-telefonica
        {--fecha= : Dia completo a sincronizar en formato Y-m-d (default: ayer)}
        {--desde= : Fecha/hora de inicio (Y-m-d H:i:s), tiene prioridad sobre --fecha}
        {--hasta= : Fecha/hora de fin (Y-m-d H:i:s), default: ahora si se usa --desde}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trae los CDR directamente del panel de la central telefonica (sin CSV manual) y los importa';

    public function handle(CentralTelefonicaSincronizacionService $service): int
    {
        [$desde, $hasta] = $this->resolverRango();

        $this->info("Sincronizando llamadas del {$desde->format('d/m/Y H:i')} al {$hasta->format('d/m/Y H:i')}...");

        $inicio = microtime(true);

        try {
            $resultado = $service->sincronizar($desde, $hasta);
        } catch (\Throwable $e) {
            $this->error('No se pudo sincronizar: ' . $e->getMessage());

            ImportacionLlamadaCentralTelefonica::create([
                'nombre_archivo' => 'auto_' . $desde->format('Y_m_d'),
                'estado' => 'error',
                'error_mensaje' => $e->getMessage(),
                'tiempo_procesamiento' => (int) round((microtime(true) - $inicio) * 1000),
            ]);

            return self::FAILURE;
        }

        ImportacionLlamadaCentralTelefonica::create([
            'nombre_archivo' => $resultado['archivo'],
            'total_registros' => $resultado['total'] + $resultado['omitidos'],
            'registros_importados' => $resultado['total'],
            'registros_omitidos' => $resultado['omitidos'],
            'estado' => 'completado',
            'tiempo_procesamiento' => (int) round((microtime(true) - $inicio) * 1000),
        ]);

        $this->info("{$resultado['total']} llamadas procesadas" . ($resultado['omitidos'] > 0 ? ", {$resultado['omitidos']} omitidas" : '') . " ({$resultado['archivo']}).");

        return self::SUCCESS;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverRango(): array
    {
        if ($this->option('desde')) {
            $desde = Carbon::parse($this->option('desde'));
            $hasta = $this->option('hasta') ? Carbon::parse($this->option('hasta')) : now();

            return [$desde, $hasta];
        }

        $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : now()->subDay();

        $desde = $fecha->copy()->startOfDay();
        $hasta = $fecha->isToday() ? now() : $fecha->copy()->endOfDay();

        return [$desde, $hasta];
    }
}
