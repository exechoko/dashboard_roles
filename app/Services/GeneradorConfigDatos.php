<?php

namespace App\Services;

use RuntimeException;

class GeneradorConfigDatos
{
    /**
     * Claves escalares (números) que admite el objeto DATOS.
     *
     * @var array<int, string>
     */
    public const CLAVES_ESCALARES = [
        'anosServicio',
        'funcionarios',
        'camaras',
        'moviles',
        'motopatrullas',
        'unidadesOperativas',
        'llamadasPromedio',
        'dispositivosDuales',
        'usuariosBotonPanico',
    ];

    /**
     * Claves de series mensuales (arrays de enteros).
     *
     * @var array<int, string>
     */
    public const CLAVES_SERIES = [
        'armasPorMes',
        'vehiculosPorMes',
        'motosPorMes',
    ];

    /**
     * Regenera el archivo config-datos.js a partir del mapa de valores.
     *
     * @param  array<string, mixed>  $datos
     */
    public function generar(array $datos): string
    {
        $ruta = $this->rutaArchivo();
        $contenido = $this->construirContenido($datos);

        $directorio = dirname($ruta);
        if (! is_dir($directorio) || ! is_writable($directorio)) {
            throw new RuntimeException("No se puede escribir en el directorio de la web: {$directorio}");
        }

        if (is_file($ruta)) {
            @copy($ruta, $ruta . '.bak');
        }

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

    /**
     * Ruta absoluta al archivo config-datos.js de la web.
     */
    public function rutaArchivo(): string
    {
        return rtrim(config('landing.path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.config_datos_js'));
    }

    /**
     * Construye el contenido completo del archivo JS, inyectando solo el
     * objeto DATOS y manteniendo intacta la lógica (totales + aplicarDatos).
     *
     * @param  array<string, mixed>  $datos
     */
    private function construirContenido(array $datos): string
    {
        $meses = $this->jsArrayStrings($datos['meses2026'] ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']);

        $get = fn (string $clave): int => (int) ($datos[$clave] ?? 0);
        $serie = fn (string $clave): string => $this->jsArrayEnteros($datos[$clave] ?? []);

        return <<<JS
// ============================================================
//  CONFIGURACIÓN CENTRAL DE DATOS — División 911 y V.V
//  ⚠️ ARCHIVO GENERADO AUTOMÁTICAMENTE desde el panel del sistema.
//  No edites a mano: los cambios se sobrescriben al guardar en el panel.
// ============================================================

const DATOS = {
    // --- Institucional ---
    anosServicio:        {$get('anosServicio')},
    funcionarios:        {$get('funcionarios')},

    // --- Recursos / equipamiento ---
    camaras:             {$get('camaras')},
    moviles:             {$get('moviles')},
    motopatrullas:       {$get('motopatrullas')},
    unidadesOperativas:  {$get('unidadesOperativas')},

    // --- Operativo ---
    llamadasPromedio:    {$get('llamadasPromedio')},

    // --- Violencia de Género ---
    dispositivosDuales:    {$get('dispositivosDuales')},
    usuariosBotonPanico:   {$get('usuariosBotonPanico')},

    // --- Resultados 2026 por mes (los TOTALES se calculan solos más abajo) ---
    meses2026:       {$meses},
    armasPorMes:     {$serie('armasPorMes')},   // armas de fuego secuestradas
    vehiculosPorMes: {$serie('vehiculosPorMes')},   // vehículos recuperados
    motosPorMes:     {$serie('motosPorMes')}    // motovehículos recuperados
};

// --- Totales 2026 (suma automática de las series mensuales) ---
const _suma = arr => arr.reduce((a, b) => a + b, 0);
DATOS.armasSecuestradas         = _suma(DATOS.armasPorMes);
DATOS.vehiculosRecuperados      = _suma(DATOS.vehiculosPorMes);
DATOS.motovehiculosRecuperados  = _suma(DATOS.motosPorMes);

// --- Aplica los valores a los elementos del DOM ---
// (debe cargarse ANTES de anime-init.js / main.js para que la
//  animación de contadores encuentre los data-counter ya seteados)
(function aplicarDatos() {
    // [data-stat]      → contador animado (setea data-counter)
    document.querySelectorAll('[data-stat]').forEach(el => {
        const valor = DATOS[el.dataset.stat];
        if (valor !== undefined && !Array.isArray(valor)) {
            el.dataset.counter = valor;
        }
    });
    // [data-stat-text] → texto fijo, sin animación (setea textContent)
    document.querySelectorAll('[data-stat-text]').forEach(el => {
        const valor = DATOS[el.dataset.statText];
        if (valor !== undefined && !Array.isArray(valor)) {
            el.textContent = valor;
        }
    });
})();

JS;
    }

    /**
     * @param  array<int, mixed>  $valores
     */
    private function jsArrayEnteros(array $valores): string
    {
        $enteros = array_map(static fn ($v): int => (int) $v, array_values($valores));

        return '[' . implode(', ', $enteros) . ']';
    }

    /**
     * @param  array<int, mixed>  $valores
     */
    private function jsArrayStrings(array $valores): string
    {
        $items = array_map(
            static fn ($v): string => "'" . str_replace("'", "\\'", (string) $v) . "'",
            array_values($valores)
        );

        return '[' . implode(', ', $items) . ']';
    }
}
