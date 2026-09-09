<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Lee y reescribe el archivo .env preservando orden, comentarios y líneas en
 * blanco: sólo toca las líneas de las claves que efectivamente cambiaron.
 * Antes de cada escritura guarda una copia de respaldo, para poder revertir
 * a mano un cambio que haya dejado la app inaccesible.
 */
class EnvEditorService
{
    private const MAX_BACKUPS = 20;

    private string $envPath;

    public function __construct(?string $envPath = null)
    {
        $this->envPath = $envPath ?? base_path('.env');
    }

    /**
     * @return array<string, string>
     */
    public function pares(): array
    {
        $pares = [];

        foreach ($this->lineas() as $linea) {
            $par = $this->parsearLinea($linea);
            if ($par !== null) {
                $pares[$par[0]] = $par[1];
            }
        }

        return $pares;
    }

    /**
     * Reescribe el .env aplicando $cambios (clave => valor nuevo). Las claves
     * que no existían se agregan al final. Las que no cambiaron no se tocan.
     *
     * @param  array<string, string>  $cambios
     */
    public function actualizar(array $cambios): void
    {
        if ($cambios === []) {
            return;
        }

        $this->respaldar();

        $lineas = $this->lineas();
        $pendientes = $cambios;

        foreach ($lineas as $indice => $linea) {
            $par = $this->parsearLinea($linea);
            if ($par === null) {
                continue;
            }

            [$clave] = $par;
            if (array_key_exists($clave, $pendientes)) {
                $lineas[$indice] = $clave . '=' . $this->formatearValor($pendientes[$clave]);
                unset($pendientes[$clave]);
            }
        }

        if ($pendientes !== []) {
            if ($lineas !== [] && trim((string) end($lineas)) !== '') {
                $lineas[] = '';
            }

            $lineas[] = '# Agregado desde Configuración del Sistema — ' . now()->format('Y-m-d H:i');
            foreach ($pendientes as $clave => $valor) {
                $lineas[] = $clave . '=' . $this->formatearValor($valor);
            }
        }

        if (file_put_contents($this->envPath, implode("\n", $lineas) . "\n") === false) {
            throw new RuntimeException("No se pudo escribir el archivo .env: {$this->envPath}");
        }

        Artisan::call('config:clear');
    }

    private function respaldar(): void
    {
        if (!is_file($this->envPath)) {
            return;
        }

        $carpeta = storage_path('app/config-backups/env');
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $destino = $carpeta . DIRECTORY_SEPARATOR . '.env.' . now()->format('Ymd_His');
        copy($this->envPath, $destino);

        collect(glob($carpeta . DIRECTORY_SEPARATOR . '.env.*') ?: [])
            ->sortByDesc(fn (string $archivo) => filemtime($archivo) ?: 0)
            ->values()
            ->slice(self::MAX_BACKUPS)
            ->each(fn (string $archivo) => @unlink($archivo));
    }

    /**
     * @return array<int, string>
     */
    private function lineas(): array
    {
        if (!is_file($this->envPath)) {
            throw new RuntimeException("No se encontró el archivo .env en: {$this->envPath}");
        }

        $contenido = file_get_contents($this->envPath);
        if ($contenido === false) {
            throw new RuntimeException("No se pudo leer el archivo .env: {$this->envPath}");
        }

        $lineas = preg_split('/\r\n|\r|\n/', $contenido) ?: [];

        // El archivo normalmente termina en salto de línea: preg_split deja un
        // último elemento vacío que, si no se descarta, se convierte en una
        // línea en blanco extra cada vez que se reescribe el archivo.
        if ($lineas !== [] && end($lineas) === '') {
            array_pop($lineas);
        }

        return $lineas;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function parsearLinea(string $linea): ?array
    {
        $recortada = trim($linea);
        if ($recortada === '' || str_starts_with($recortada, '#')) {
            return null;
        }

        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $recortada, $coincidencias)) {
            return null;
        }

        return [$coincidencias[1], $this->desentrecomillar($coincidencias[2])];
    }

    private function desentrecomillar(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        $primero = $valor[0];
        if (($primero === '"' || $primero === "'") && strlen($valor) > 1 && str_ends_with($valor, $primero)) {
            $interior = substr($valor, 1, -1);

            return $primero === '"' ? str_replace(['\\"', '\\n'], ['"', "\n"], $interior) : $interior;
        }

        return $valor;
    }

    private function formatearValor(string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        if (preg_match('/[\s#"\'\\\\]/', $valor)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $valor) . '"';
        }

        return $valor;
    }
}
