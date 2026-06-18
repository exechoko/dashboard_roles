<?php

namespace App\Services;

use App\Models\WebDependencia;
use RuntimeException;

class GeneradorDependenciasJs
{
    /**
     * Regenera js/dependencias-data.js a partir de la BD (en orden).
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

        $dependencias = WebDependencia::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (WebDependencia $d): array => [
                'nombre'    => $d->nombre,
                'categoria' => $d->categoria,
                'direccion' => $d->direccion ?? '',
                'telefonos' => array_values(array_filter($d->telefonos ?? [], fn ($t) => trim((string) $t) !== '')),
                'tags'      => array_values(array_filter($d->tags ?? [], fn ($t) => trim((string) $t) !== '')),
            ])
            ->all();

        $json = json_encode($dependencias, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $contenido = <<<JS
/* ============================================================================
   DATOS DE DEPENDENCIAS / COMISARÍAS
   ⚠️ ARCHIVO GENERADO AUTOMÁTICAMENTE desde el panel del sistema.
   No editar a mano: los cambios se sobrescriben al guardar en el panel.
   Para administrar las dependencias usá: Administrar Web → Dependencias.
   ========================================================================== */

window.DEPENDENCIAS = {$json};

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
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.dependencias_js'));
    }
}
