<?php

namespace Database\Seeders;

use App\Models\WebDependencia;
use Illuminate\Database\Seeder;

/**
 * Importa las dependencias actuales de js/dependencias-data.js a la BD.
 * Ejecutar una sola vez: php artisan db:seed --class=WebDependenciaSeeder
 */
class WebDependenciaSeeder extends Seeder
{
    public function run(): void
    {
        $dependencias = [
            ['Comisaría 1ª', 'ciudad-parana', 'Tucumán 55 – Paraná', ['4206224', '3434601942']],
            ['Comisaría 2ª', 'ciudad-parana', 'Gualeguaychú 280 – Paraná', ['4206212', '3436221365']],
            ['Comisaría 3ª', 'ciudad-parana', 'Alejandro Carbó 800-898 – Paraná', ['4206223', '3434601939']],
            ['Comisaría 4ª', 'ciudad-parana', 'Fco. Soler 1230 – Paraná', ['4206222', '3436221379']],
            ['Comisaría 5ª', 'ciudad-parana', 'Florentino Ameghino 383 – Paraná', ['4206202', '3436221998']],
            ['Comisaría 6ª', 'ciudad-parana', 'Av. Ejército 1681 – Paraná', ['4206208', '3434602088']],
            ['Comisaría 7ª', 'ciudad-parana', 'Fraternidad y Pedro Londero – Paraná', ['4206211', '3434601988']],
            ['Comisaría 8ª', 'ciudad-parana', 'Laurencena 181 – Paraná', ['4206214', '3436222001']],
            ['Comisaría 9ª', 'ciudad-parana', 'Facundo 2361 – Paraná', ['4206209', '3436222006']],
            ['Comisaría 10ª', 'ciudad-parana', 'Almte. Thomas Cochrane 599-699 – Paraná', ['4206204', '3436222017']],
            ['Comisaría 11ª', 'ciudad-parana', 'Av. Estrada 3350 – Paraná', ['4211003', '3434601989']],
            ['Comisaría 12ª', 'ciudad-parana', 'Lola Mora y Mario Monti – Paraná', ['4206216', '3436222029']],
            ['Comisaría 13ª', 'ciudad-parana', 'Juan Báez 1-99 – Paraná', ['4206203', '3434601941']],
            ['Comisaría 14ª', 'ciudad-parana', 'Rondeau 2200 – Paraná', ['4301550', '3434602089']],
            ['Comisaría 15ª', 'ciudad-parana', 'Av. Jorge Newbery y Gonzalo de Berceo – Paraná', ['4206205', '3435139278']],
            ['Comisaría 16ª', 'ciudad-parana', 'Casiano Calderón y Clarck – Paraná', ['4206217', '3434601940']],
            ['Comisaría 17ª', 'ciudad-parana', 'Osinalde 367 – Paraná', ['4211002', '3434601987']],
            ['Comisaría Colonia Avellaneda', 'ciudad-colonia-avellaneda', 'Yáñez Martín y Moreno – Colonia Avellaneda', ['4979052', '3435163128']],
            ['Comisaría Oro Verde', 'ciudad-oro-verde', 'Ruta 11 S/N – Oro Verde', ['4975082', '3434702695']],
            ['Comisaría San Benito', 'ciudad-san-benito', 'Friuli y San Martín – San Benito', ['4973014', '3434601991']],
            ['Departamental Paraná', 'departamental', 'Gualeguaychu 322 – Paraná', ['4209063']],
            ['Delitos Económicos', 'divisiones', 'Soler 1230 – Paraná', ['4209997', '3435361503']],
        ];

        $orden = 0;
        foreach ($dependencias as [$nombre, $categoria, $direccion, $telefonos]) {
            WebDependencia::updateOrCreate(
                ['nombre' => $nombre, 'categoria' => $categoria],
                [
                    'direccion' => $direccion,
                    'telefonos' => $telefonos,
                    'tags'      => [],
                    'orden'     => ++$orden,
                ],
            );
        }

        $this->command->info('Dependencias importadas a web_dependencias.');
    }
}
