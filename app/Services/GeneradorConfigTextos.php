<?php

namespace App\Services;

use App\Models\WebTexto;
use RuntimeException;

class GeneradorConfigTextos
{
    /**
     * Catálogo plano de textos editables: clave => metadatos.
     *
     * @return array<string, array{label: string, tipo: string, default: string, grupo: string, grupoLabel: string}>
     */
    public static function catalogo(): array
    {
        $catalogo = [];
        foreach ((array) config('textos_web', []) as $grupo => $datos) {
            foreach ($datos['textos'] ?? [] as $clave => $meta) {
                $catalogo[$clave] = [
                    'label'      => $meta['label'] ?? $clave,
                    'tipo'       => $meta['tipo'] ?? 'text',
                    'default'    => $meta['default'] ?? '',
                    'grupo'      => $grupo,
                    'grupoLabel' => $datos['label'] ?? $grupo,
                ];
            }
        }

        return $catalogo;
    }

    /**
     * Valor actual de cada texto: el guardado en BD o, si no existe, el default del catálogo.
     *
     * @return array<string, string>
     */
    public static function valores(): array
    {
        $guardados = WebTexto::comoMapa();
        $valores = [];
        foreach (self::catalogo() as $clave => $meta) {
            $valores[$clave] = $guardados[$clave] ?? $meta['default'];
        }

        return $valores;
    }

    /**
     * Regenera el archivo config-textos.js que aplica los textos en la web.
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

        $json = json_encode(self::valores(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $contenido = <<<JS
// ============================================================
//  TEXTOS EDITABLES — División 911 y V.V
//  ⚠️ ARCHIVO GENERADO AUTOMÁTICAMENTE desde el panel del sistema.
//  Reemplaza el contenido de los elementos con [data-edit] (texto plano)
//  y [data-edit-html] (contenido con formato).
// ============================================================

const TEXTOS = {$json};

(function aplicarTextos() {
    document.querySelectorAll('[data-edit]').forEach(function (el) {
        var valor = TEXTOS[el.dataset.edit];
        if (valor !== undefined && valor !== null) {
            el.textContent = valor;
        }
    });
    document.querySelectorAll('[data-edit-html]').forEach(function (el) {
        var valor = TEXTOS[el.dataset.editHtml];
        if (valor !== undefined && valor !== null) {
            el.innerHTML = valor;
        }
    });
})();

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
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.config_textos_js'));
    }
}
