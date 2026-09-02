<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DescargaCategoria;
use Illuminate\Support\Str;

class SeederCategoriasDescargas extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Documentos',
                'descripcion' => 'PDF, Word, textos generales',
                'icono' => 'fas fa-file-alt',
                'color' => '#3498db',
                'orden' => 1,
            ],
            [
                'nombre' => 'Imágenes',
                'descripcion' => 'JPG, PNG, GIF, etc.',
                'icono' => 'fas fa-image',
                'color' => '#27ae60',
                'orden' => 2,
            ],
            [
                'nombre' => 'Videos',
                'descripcion' => 'MP4, AVI, MOV, etc.',
                'icono' => 'fas fa-video',
                'color' => '#e74c3c',
                'orden' => 3,
            ],
            [
                'nombre' => 'Formularios',
                'descripcion' => 'Formularios oficiales',
                'icono' => 'fas fa-file-invoice',
                'color' => '#9b59b6',
                'orden' => 4,
            ],
            [
                'nombre' => 'Manuales',
                'descripcion' => 'Manuales de usuario, procedimientos',
                'icono' => 'fas fa-book',
                'color' => '#f39c12',
                'orden' => 5,
            ],
            [
                'nombre' => 'Normativas',
                'descripcion' => 'Leyes, reglamentos, normativas',
                'icono' => 'fas fa-gavel',
                'color' => '#34495e',
                'orden' => 6,
            ],
            [
                'nombre' => 'Informes',
                'descripcion' => 'Reportes, estadísticas',
                'icono' => 'fas fa-chart-bar',
                'color' => '#16a085',
                'orden' => 7,
            ],
            [
                'nombre' => 'Planillas',
                'descripcion' => 'Excel, CSV, hojas de cálculo',
                'icono' => 'fas fa-file-excel',
                'color' => '#2ecc71',
                'orden' => 8,
            ],
            [
                'nombre' => 'Presentaciones',
                'descripcion' => 'PowerPoints',
                'icono' => 'fas fa-file-powerpoint',
                'color' => '#e67e22',
                'orden' => 9,
            ],
            [
                'nombre' => 'Archivos Comprimidos',
                'descripcion' => 'ZIP, RAR, 7Z',
                'icono' => 'fas fa-file-archive',
                'color' => '#95a5a6',
                'orden' => 10,
            ],
        ];

        foreach ($categorias as $categoria) {
            DescargaCategoria::firstOrCreate(
                ['slug' => Str::slug($categoria['nombre'])],
                $categoria
            );
        }

        $this->command->info('Categorías de descargas creadas correctamente.');
    }
}
