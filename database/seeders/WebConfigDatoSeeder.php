<?php

namespace Database\Seeders;

use App\Models\WebConfigDato;
use Illuminate\Database\Seeder;

/**
 * Carga los valores actuales de config-datos.js en la BD.
 * Ejecutar: php artisan db:seed --class=WebConfigDatoSeeder
 */
class WebConfigDatoSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            'anosServicio'        => 14,
            'funcionarios'        => 439,
            'camaras'             => 400,
            'moviles'             => 32,
            'motopatrullas'       => 32,
            'unidadesOperativas'  => 2,
            'llamadasPromedio'    => 590,
            'dispositivosDuales'  => 115,
            'usuariosBotonPanico' => 850,
            'meses2026'           => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            'armasPorMes'         => [9, 0, 9, 4, 8, 2],
            'vehiculosPorMes'     => [2, 1, 3, 3, 2, 1],
            'motosPorMes'         => [3, 4, 7, 4, 7, 6],
        ];

        foreach ($datos as $clave => $valor) {
            WebConfigDato::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }

        $this->command->info('Datos de la web cargados en web_config_datos.');
    }
}
