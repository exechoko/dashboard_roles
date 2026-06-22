<?php

namespace App\Services;

use App\Models\WebGaleriaImagen;
use RuntimeException;

class GeneradorGaleriaJs
{
    /**
     * Regenera js/galeria-data.js a partir de la BD (en orden).
     */
    public function generar(): string
    {
        $ruta = $this->rutaArchivo();
        $directorio = dirname($ruta);

        if (! is_dir($directorio) || ! is_writable($directorio)) {
            throw new RuntimeException("No se puede escribir en el directorio de la web: {$directorio}");
        }

        if (is_file($ruta)) {
            @copy($ruta, $ruta . '.bak');
        }

        $imagenes = WebGaleriaImagen::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (WebGaleriaImagen $i): array => [
                'src'       => $i->imagen,
                'titulo'    => $i->titulo,
                'categoria' => $i->categoria,
            ])
            ->all();

        $json = json_encode($imagenes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $contenido = <<<JS
/* ============================================================================
   GALERÍA (slider de galeria.html)
   ⚠️ ARCHIVO GENERADO AUTOMÁTICAMENTE desde el panel del sistema.
   No editar a mano. Administrá las imágenes en: Administrar Web → Galería.
   ========================================================================== */

window.GALERIA = {$json};

JS;

        $temporal = $ruta . '.tmp';
        if (file_put_contents($temporal, $contenido) === false) {
            throw new RuntimeException("No se pudo escribir el archivo temporal: {$temporal}");
        }
        if (! @rename($temporal, $ruta)) {
            @unlink($temporal);
            throw new RuntimeException("No se pudo reemplazar el archivo: {$ruta}");
        }

        return $ruta;
    }

    public function rutaArchivo(): string
    {
        return rtrim(config('landing.path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.galeria_js'));
    }
}
