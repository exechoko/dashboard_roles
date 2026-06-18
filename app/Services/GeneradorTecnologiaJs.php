<?php

namespace App\Services;

use App\Models\WebTechCard;
use RuntimeException;

class GeneradorTecnologiaJs
{
    /**
     * Regenera js/tecnologia-data.js a partir de la BD (en orden).
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

        $imgDir = trim(config('landing.tecnologia_img_dir'), '/');

        $cards = WebTechCard::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (WebTechCard $c): array => [
                'titulo' => $c->titulo,
                'texto'  => $c->texto,
                'color'  => $c->color,
                'imagen' => $c->imagen ? $imgDir . '/' . $c->imagen : null,
            ])
            ->all();

        $json = json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $contenido = <<<JS
/* ============================================================================
   CARDS DE TECNOLOGÍA
   ⚠️ ARCHIVO GENERADO AUTOMÁTICAMENTE desde el panel del sistema.
   No editar a mano. Administrá las cards en: Administrar Web → Textos → Tecnología.
   ========================================================================== */

window.TECNOLOGIA = {$json};

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
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.tecnologia_js'));
    }
}
