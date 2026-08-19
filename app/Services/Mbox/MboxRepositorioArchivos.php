<?php

namespace App\Services\Mbox;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use RuntimeException;

/**
 * Descubre las carpetas de oficina y los archivos .mbox dentro de
 * config('mbox.ruta'), validando siempre que la ruta resuelta quede dentro
 * de esa carpeta raíz.
 */
class MboxRepositorioArchivos
{
    /**
     * Subcarpetas de primer nivel encontradas en la raíz configurada, con un
     * nombre "lindo" sugerido para el buzón.
     *
     * @return array<int, array{carpeta: string, nombre_sugerido: string, ya_registrado: bool}>
     */
    public function oficinas(): array
    {
        $raiz = $this->raiz();

        if (!is_dir($raiz)) {
            return [];
        }

        $carpetasRegistradas = MailBuzon::pluck('carpeta')->all();

        $oficinas = [];
        foreach (scandir($raiz) as $entrada) {
            if ($entrada === '.' || $entrada === '..') {
                continue;
            }
            if (!is_dir($raiz.DIRECTORY_SEPARATOR.$entrada)) {
                continue;
            }
            $oficinas[] = [
                'carpeta' => $entrada,
                'nombre_sugerido' => $this->nombreLindo($entrada),
                'ya_registrado' => in_array($entrada, $carpetasRegistradas, true),
            ];
        }

        return $oficinas;
    }

    /**
     * Archivos .mbox hallados recursivamente dentro de la carpeta del buzón,
     * marcando cuáles ya están registrados y si cambiaron de tamaño/fecha.
     *
     * @return array<int, array{ruta_absoluta: string, nombre_archivo: string, tamano_bytes: int, mtime: \DateTimeImmutable, registro: ?MailArchivo, requiere_reindexar: bool}>
     */
    public function archivosDe(MailBuzon $buzon): array
    {
        $carpeta = $this->rutaOficina($buzon->carpeta);

        if (!is_dir($carpeta)) {
            return [];
        }

        $registrados = $buzon->archivos()->get()->keyBy('ruta_absoluta');

        $resultado = [];
        foreach ($this->globRecursivo($carpeta, '*.mbox') as $ruta) {
            $tamano = filesize($ruta);
            $mtime = (new \DateTimeImmutable())->setTimestamp(filemtime($ruta));
            $registro = $registrados->get($ruta);

            $resultado[] = [
                'ruta_absoluta' => $ruta,
                'nombre_archivo' => basename($ruta),
                'tamano_bytes' => $tamano,
                'mtime' => $mtime,
                'registro' => $registro,
                'requiere_reindexar' => $registro !== null
                    && ((int) $registro->tamano_bytes !== $tamano
                        || $registro->mtime_archivo?->timestamp !== $mtime->getTimestamp()),
            ];
        }

        return $resultado;
    }

    /**
     * Valida que $ruta quede dentro de la carpeta raíz configurada y que sea
     * un .mbox existente. Lanza si no.
     */
    public function validarRutaMbox(string $ruta): string
    {
        $real = realpath($ruta);
        $raizReal = realpath($this->raiz());

        if ($real === false || $raizReal === false || !str_starts_with($real, $raizReal)) {
            throw new RuntimeException("Ruta fuera de la carpeta de backups de correo: {$ruta}");
        }

        if (strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'mbox') {
            throw new RuntimeException("El archivo no tiene extensión .mbox: {$ruta}");
        }

        return $real;
    }

    public function raiz(): string
    {
        return config('mbox.ruta');
    }

    public function rutaOficina(string $carpeta): string
    {
        return rtrim($this->raiz(), '\\/').DIRECTORY_SEPARATOR.$carpeta;
    }

    private function nombreLindo(string $carpeta): string
    {
        return trim(str_replace(['_', '-'], ' ', $carpeta));
    }

    /**
     * @return array<int, string>
     */
    private function globRecursivo(string $carpeta, string $patron): array
    {
        $encontrados = glob($carpeta.DIRECTORY_SEPARATOR.$patron) ?: [];

        foreach (glob($carpeta.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [] as $subcarpeta) {
            $encontrados = array_merge($encontrados, $this->globRecursivo($subcarpeta, $patron));
        }

        return $encontrados;
    }
}
