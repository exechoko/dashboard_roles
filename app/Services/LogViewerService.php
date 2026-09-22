<?php

namespace App\Services;

use Illuminate\Support\Collection;

class LogViewerService
{
    /**
     * Niveles PSR-3 que puede escribir Monolog, en el orden en que Laravel
     * los imprime (`[fecha] entorno.NIVEL: mensaje`).
     *
     * @var array<int, string>
     */
    public const NIVELES = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];

    /**
     * Si el archivo pesa más que esto, solo se lee el último tramo (evita
     * agotar memoria con un log viejo que quedó gigante).
     */
    private const MAX_BYTES_A_LEER = 10 * 1024 * 1024;

    private string $directorio;

    public function __construct()
    {
        $this->directorio = storage_path('logs');
    }

    /**
     * Archivos de log de la aplicación disponibles para ver, más recientes
     * primero (los `laravel-YYYY-MM-DD.log` de la rotación diaria, más el
     * histórico `laravel.log` si todavía existe de antes del cambio a rotación).
     *
     * @return array<int, array{nombre: string, tamano_mb: float, modificado: \Illuminate\Support\Carbon}>
     */
    public function archivosDisponibles(): array
    {
        if (!is_dir($this->directorio)) {
            return [];
        }

        $archivos = collect(glob($this->directorio . DIRECTORY_SEPARATOR . 'laravel*.log') ?: [])
            ->map(function (string $ruta) {
                return [
                    'nombre'      => basename($ruta),
                    'tamano_mb'   => round(filesize($ruta) / 1024 / 1024, 2),
                    'modificado'  => \Illuminate\Support\Carbon::createFromTimestamp(filemtime($ruta)),
                ];
            })
            ->sortByDesc('modificado')
            ->values();

        return $archivos->all();
    }

    /**
     * Lee y parsea las entradas de un archivo de log, más recientes primero.
     *
     * @return Collection<int, array{fecha: string, entorno: string, nivel: string, mensaje: string, detalle: string}>
     */
    public function leerEntradas(string $archivo, ?string $nivel = null, ?string $texto = null): Collection
    {
        $ruta = $this->rutaSegura($archivo);

        if ($ruta === null || !is_file($ruta)) {
            return collect();
        }

        $contenido = $this->leerContenido($ruta);
        $entradas = $this->parsear($contenido);

        if ($nivel) {
            $entradas = $entradas->where('nivel', strtoupper($nivel));
        }

        if ($texto) {
            $textoBusqueda = mb_strtolower($texto);
            $entradas = $entradas->filter(
                fn (array $entrada) => mb_stripos($entrada['mensaje'], $textoBusqueda) !== false
                    || mb_stripos($entrada['detalle'], $textoBusqueda) !== false
            );
        }

        return $entradas->values();
    }

    /**
     * Evita path traversal: solo se puede pedir un archivo *.log que
     * realmente esté dentro de storage/logs.
     */
    private function rutaSegura(string $archivo): ?string
    {
        $nombre = basename($archivo);

        if (!str_ends_with($nombre, '.log')) {
            return null;
        }

        return $this->directorio . DIRECTORY_SEPARATOR . $nombre;
    }

    private function leerContenido(string $ruta): string
    {
        $tamano = filesize($ruta);

        if ($tamano <= self::MAX_BYTES_A_LEER) {
            return file_get_contents($ruta) ?: '';
        }

        $handle = fopen($ruta, 'r');
        fseek($handle, -self::MAX_BYTES_A_LEER, SEEK_END);
        // Descarta la primera línea parcial del tramo leído.
        fgets($handle);
        $contenido = stream_get_contents($handle);
        fclose($handle);

        return $contenido ?: '';
    }

    /**
     * Cada entrada empieza con una línea `[fecha] entorno.NIVEL: mensaje`
     * (formato por defecto de Monolog en Laravel); las líneas siguientes
     * (stack trace, contexto) se acumulan como "detalle" hasta la próxima
     * entrada.
     *
     * @return Collection<int, array{fecha: string, entorno: string, nivel: string, mensaje: string, detalle: string}>
     */
    private function parsear(string $contenido): Collection
    {
        $patron = '/^\[(?<fecha>\d{4}-\d{2}-\d{2}[T ][\d:.+\-]+)\] (?<entorno>\w+)\.(?<nivel>[A-Z]+): (?<mensaje>.*)$/';

        $entradas = [];
        $actual = null;

        foreach (preg_split('/\r\n|\r|\n/', $contenido) as $linea) {
            if (preg_match($patron, $linea, $m)) {
                if ($actual !== null) {
                    $entradas[] = $actual;
                }
                $actual = [
                    'fecha'   => $m['fecha'],
                    'entorno' => $m['entorno'],
                    'nivel'   => $m['nivel'],
                    'mensaje' => $m['mensaje'],
                    'detalle' => '',
                ];
                continue;
            }

            if ($actual !== null && $linea !== '') {
                $actual['detalle'] .= ($actual['detalle'] === '' ? '' : "\n") . $linea;
            }
        }

        if ($actual !== null) {
            $entradas[] = $actual;
        }

        return collect(array_reverse($entradas));
    }
}
