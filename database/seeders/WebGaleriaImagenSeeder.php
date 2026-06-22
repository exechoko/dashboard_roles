<?php

namespace Database\Seeders;

use App\Models\WebGaleriaImagen;
use Illuminate\Database\Seeder;

/**
 * Importa las imágenes que estaban hardcodeadas en js/gallery.js para que la
 * galería arranque con el contenido actual y se siga administrando desde el panel.
 * Ejecutar: php artisan db:seed --class=WebGaleriaImagenSeeder
 */
class WebGaleriaImagenSeeder extends Seeder
{
    public function run(): void
    {
        $imagenes = [
            ['images/fb011ae3-ece8-4788-83bd-062508e5b2ca.webp', 'Sede de la División 911', 'Instalaciones'],
            ['images/sala-telefono.webp', 'Sala de atención telefónica', 'Operaciones'],
            ['images/IMG_2231.webp', 'Operadores en servicio', 'Operaciones'],
            ['images/IMG_2237.webp', 'Centro de despacho 911', 'Operaciones'],
            ['images/sala-despacho.webp', 'Sala de despacho', 'Operaciones'],
            ['images/IMG_2233.webp', 'Despacho por radio', 'Operaciones'],
            ['images/DSC_2376.webp', 'Centro de monitoreo urbano', 'Videovigilancia'],
            ['images/video_vigilancia.webp', 'Videowall de cámaras', 'Videovigilancia'],
            ['images/DSC_2526.webp', 'Monitoreo en tiempo real', 'Videovigilancia'],
            ['images/DSC_2400.webp', 'Operadora de videovigilancia', 'Videovigilancia'],
            ['images/patrulla.webp', 'Flota de patrulleros 911', 'Móviles'],
            ['images/moviles.webp', 'Móviles policiales', 'Móviles'],
            ['images/IMG_1299.webp', 'Patrulleros en formación', 'Móviles'],
            ['images/patrulla1.webp', 'Patrullero 911', 'Móviles'],
            ['images/unidad-operativa.webp', 'Unidad móvil operativa', 'Operativos'],
            ['images/IMG-20251004-WA0001.webp', 'Unidad móvil (vista aérea)', 'Operativos'],
            ['images/IMG_2011.webp', 'Despliegue territorial', 'Operativos'],
            ['images/IMG_2014.webp', 'Operativo con móviles', 'Operativos'],
            ['images/IMG_2084.webp', 'Operativo urbano', 'Operativos'],
            ['images/IMG_1783.webp', 'Unidad móvil en terreno', 'Operativos'],
            ['images/IMG_1957.webp', 'Unidad móvil nocturna', 'Operativos'],
            ['images/IMG_2085.webp', 'Monitoreo en unidad móvil', 'Tecnología'],
            ['images/IMG_1778.webp', 'Puesto de monitoreo móvil', 'Tecnología'],
            ['images/IMG_2016.webp', 'Formación de personal', 'Personal'],
            ['images/IMG_2017.webp', 'Despliegue de efectivos', 'Personal'],
        ];

        foreach ($imagenes as $orden => [$src, $titulo, $categoria]) {
            WebGaleriaImagen::firstOrCreate(
                ['imagen' => $src],
                ['titulo' => $titulo, 'categoria' => $categoria, 'orden' => $orden],
            );
        }
    }
}
