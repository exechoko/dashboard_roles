<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Busca audios de modulaciones de radio TETRA en el disco local, con la misma
 * estructura que las grabaciones telefónicas ({base}\YYYY\YYYY_MM\Operador\...).
 *
 * Nombre de archivo esperado:
 *   {id}_0_{canal}_{tipo}_{YYYYMMDD}_{HHMMSS}_xf_{dur}s.mp3
 * Ej: 105106464_0_GENERAL (Grupo) (TETRA)_1_20260601_061556_0f_21s.mp3
 *     105106590_0_Escucha[MX1]_4_20260601_064347_0f_8s.mp3
 *     105106538_0_Multiconferencia_1_20260601_064040_0f_3s.mp3
 *
 * El número de tipo no distingue la fuente (las modulaciones usan tanto "1" como "4").
 * Las llamadas telefónicas se marcan con "(RDSI)"; todo lo demás (TETRA, Multiconferencia,
 * Escucha, etc.) es una modulación de radio. Como no tienen teléfono, se cruzan sólo por
 * ventana de tiempo.
 */
class CecocoModulacionesLocalService
{
    private string $baseDir;
    private string $marcadorTelefonia;

    private const EXTENSIONES_AUDIO = ['mp3', 'wav', 'ogg', 'aac'];

    public function __construct()
    {
        $this->baseDir           = rtrim(config('grabador.recordings_path', 'G:\\Audios Cecoco'), '\\/');
        $this->marcadorTelefonia = (string) config('grabador.marcador_telefonia', '(RDSI)');
    }

    /**
     * Busca modulaciones en disco dentro de una ventana de tiempo.
     *
     * @return array{modulaciones: array<int, array<string, mixed>>, ventana: array{desde: string, hasta: string}, fuente: string}
     */
    public function buscarModulaciones(Carbon $desde, Carbon $hasta): array
    {
        $modulaciones = [];
        $vistos       = [];
        $realBase     = realpath($this->baseDir) ?: $this->baseDir;

        if (!is_dir($this->baseDir)) {
            Log::debug('CecocoModulacionesLocalService: directorio base no existe', ['baseDir' => $this->baseDir]);

            return ['modulaciones' => [], 'ventana' => $this->ventana($desde, $hasta), 'fuente' => 'local'];
        }

        $cursor = $desde->copy()->startOfMinute();

        while ($cursor->lte($hasta)) {
            $dir = $this->baseDir
                 . DIRECTORY_SEPARATOR . $cursor->format('Y')
                 . DIRECTORY_SEPARATOR . $cursor->format('Y_m');

            if (is_dir($dir)) {
                // Patrón por minuto: cualquier audio con el timestamp _YYYYMMDD_HHMM.
                $prefijo  = $cursor->format('Ymd') . '_' . $cursor->format('Hi');
                $pattern  = $dir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*_' . $prefijo . '*';
                $archivos = glob($pattern, GLOB_NOSORT) ?: [];

                foreach ($archivos as $filepath) {
                    $real = realpath($filepath) ?: $filepath;
                    if (!str_starts_with($real, $realBase)) {
                        continue;
                    }

                    $filename = basename($filepath);

                    // Sólo audios y sólo modulaciones (excluir llamadas telefónicas).
                    if (!in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::EXTENSIONES_AUDIO, true)) {
                        continue;
                    }
                    if ($this->marcadorTelefonia !== '' && str_contains($filename, $this->marcadorTelefonia)) {
                        continue;
                    }

                    if (isset($vistos[$filename])) {
                        continue;
                    }
                    $vistos[$filename] = true;

                    $modulacion = $this->parsearNombreArchivo($filename, $filepath);
                    if (!$modulacion) {
                        continue;
                    }

                    $fechaArchivo = Carbon::parse($modulacion['fechaInicio']);
                    if ($fechaArchivo->between($desde, $hasta)) {
                        $modulaciones[] = $modulacion;
                    }
                }
            }

            $cursor->addMinute();
        }

        usort($modulaciones, fn ($a, $b) => strcmp($a['fechaInicio'], $b['fechaInicio']));

        Log::info('CecocoModulacionesLocalService: modulaciones encontradas', [
            'desde' => $desde->format('Y-m-d H:i:s'),
            'hasta' => $hasta->format('Y-m-d H:i:s'),
            'total' => count($modulaciones),
        ]);

        return [
            'modulaciones' => $modulaciones,
            'ventana'      => $this->ventana($desde, $hasta),
            'fuente'       => 'local',
        ];
    }

    /**
     * Verifica que el path sea válido y esté dentro del directorio base.
     */
    public function validarPath(string $filepath): bool
    {
        $real     = realpath($filepath);
        $realBase = realpath($this->baseDir);

        if (!$real || !$realBase) {
            return false;
        }

        return str_starts_with($real, $realBase);
    }

    /**
     * Extrae metadatos del nombre del archivo de modulación.
     *
     * @return array<string, mixed>|null
     */
    private function parsearNombreArchivo(string $filename, string $filepath): ?array
    {
        // Fecha y hora del nombre: _YYYYMMDD_HHMMSS_.
        if (!preg_match('/_(\d{8})_(\d{6})_/', $filename, $m)) {
            return null;
        }

        try {
            $fechaInicio = Carbon::createFromFormat('Ymd His', $m[1] . ' ' . $m[2]);
        } catch (\Exception $e) {
            return null;
        }

        // Duración en segundos.
        $duracion = '';
        if (preg_match('/_(\d+)s\.[a-z0-9]+$/i', $filename, $dm)) {
            $seg      = (int) $dm[1];
            $duracion = sprintf('%02d:%02d', intdiv($seg, 60), $seg % 60);
        }

        // Canal/etiqueta: lo que está entre "_0_" y "_{tipo}_{fecha}_{hora}_".
        $canal = '';
        if (preg_match('/_0_(.+?)_\d+_\d{8}_\d{6}_/', $filename, $cm)) {
            $canal = trim($cm[1]);
        }

        return [
            'fechaInicio' => $fechaInicio->format('Y-m-d H:i:s'),
            'duracion'    => $duracion,
            'canal'       => $canal,
            'operador'    => basename(dirname($filepath)),
            'path'        => $filepath,
            'fuente'      => 'local',
        ];
    }

    /**
     * @return array{desde: string, hasta: string}
     */
    private function ventana(Carbon $desde, Carbon $hasta): array
    {
        return [
            'desde' => $desde->format('Y-m-d H:i:s'),
            'hasta' => $hasta->format('Y-m-d H:i:s'),
        ];
    }
}
