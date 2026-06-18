<?php

namespace App\Services;

use App\Models\WebHistoriaCard;
use RuntimeException;

class GeneradorHistoriaJs
{
    /**
     * Regenera js/historia-data.js a partir de la BD (en orden).
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

        $cards = WebHistoriaCard::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (WebHistoriaCard $c): array => [
                'anio'   => $c->anio,
                'titulo' => $c->titulo,
                'texto'  => $c->texto,
                'tag'    => $c->tag ?? '',
            ])
            ->all();

        $json = json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $contenido = <<<JS
/* ============================================================================
   LÍNEA DE TIEMPO — Página Historia
   ⚠️ ARCHIVO GENERADO AUTOMÁTICAMENTE desde el panel del sistema.
   No editar a mano. Administrá las cards en: Administrar Web → Textos → Historia.
   ========================================================================== */

window.HISTORIA = {$json};

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
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.historia_js'));
    }
}
