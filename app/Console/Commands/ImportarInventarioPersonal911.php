<?php

namespace App\Console\Commands;

use App\Services\Personal911ImportService;
use Illuminate\Console\Command;

class ImportarInventarioPersonal911 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armas:importar-personal911';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa armas, chalecos y asignaciones desde la base personal911';

    /**
     * Execute the console command.
     */
    public function handle(Personal911ImportService $service): int
    {
        $resultado = $service->importar();

        $this->info("Funcionarios procesados: {$resultado['procesados']}");
        $this->info("Armas sincronizadas: {$resultado['armas']}");
        $this->info("Chalecos sincronizados: {$resultado['chalecos']}");

        if ($resultado['conflictos_armas'] !== []) {
            $this->warn('Armas duplicadas sin asignar:');
            $this->table(
                ['Arma', 'Funcionarios'],
                array_map(
                    fn (array $item): array => [$item['numero'], $item['funcionarios']],
                    $resultado['conflictos_armas']
                )
            );
        }

        if ($resultado['conflictos_chalecos'] !== []) {
            $this->warn('Chalecos duplicados sin asignar:');
            $this->table(
                ['Chaleco', 'Funcionarios'],
                array_map(
                    fn (array $item): array => [$item['numero'], $item['funcionarios']],
                    $resultado['conflictos_chalecos']
                )
            );
        }

        if ($resultado['observaciones_sin_interpretar'] !== []) {
            $this->warn('Observaciones de chalecos que requieren revisión:');
            $this->table(
                ['L.P.', 'Observación'],
                array_map(
                    fn (array $item): array => [$item['lp'], $item['observacion']],
                    $resultado['observaciones_sin_interpretar']
                )
            );
        }

        return self::SUCCESS;
    }
}
