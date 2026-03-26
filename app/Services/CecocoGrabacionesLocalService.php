<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CecocoGrabacionesLocalService
{
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = rtrim(config('cecoco.recordings_path', 'G:\\Audios Cecoco'), '\\/');
    }

    /**
     * Busca grabaciones en el disco local.
     * Estructura esperada: {baseDir}\YYYY\YYYY_MM\Operador\{id}_0_{telefono} (RDSI)_1_{YYYYMMDD}_{HHMMSS}_xf_Xs.mp3
     *
     * @return array{grabaciones: array, ventana: array, fuente: string}
     */
    public function buscarGrabaciones(
        string $telefono,
        Carbon $fechaEvento,
        int    $minAntes   = 5,
        int    $minDespues = 30
    ): array {
        // Validar que el teléfono sea numérico para evitar path injection
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        if (empty($telefono)) {
            return ['grabaciones' => [], 'ventana' => [], 'fuente' => 'local'];
        }

        $desde = $fechaEvento->copy()->subMinutes($minAntes);
        $hasta = $fechaEvento->copy()->addMinutes($minDespues);

        Log::info('CecocoGrabacionesLocalService: buscando grabaciones', [
            'telefono' => $telefono,
            'desde'    => $desde->format('Y-m-d H:i:s'),
            'hasta'    => $hasta->format('Y-m-d H:i:s'),
            'baseDir'  => $this->baseDir,
        ]);

        // Determinar los meses a buscar (puede cruzar límite de mes)
        $meses = [];
        $cursor = $desde->copy()->startOfMonth();
        while ($cursor->lte($hasta)) {
            $meses[] = $cursor->copy();
            $cursor->addMonth();
        }

        $grabaciones = [];

        foreach ($meses as $mes) {
            $year      = $mes->format('Y');
            $yearMonth = $mes->format('Y_m');
            $dir       = $this->baseDir . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $yearMonth;

            if (!is_dir($dir)) {
                Log::debug('CecocoGrabacionesLocalService: directorio no existe', ['dir' => $dir]);
                continue;
            }

            // Buscar en todas las subcarpetas (operadores) archivos que contengan el teléfono
            $pattern = $dir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . $telefono . '*';
            $archivos = glob($pattern, GLOB_NOSORT);

            Log::debug('CecocoGrabacionesLocalService: glob resultado', [
                'pattern'  => $pattern,
                'encontrados' => $archivos ? count($archivos) : 0,
            ]);

            if (!$archivos) {
                continue;
            }

            foreach ($archivos as $filepath) {
                // Verificar que el path esté dentro del directorio base (seguridad)
                if (!str_starts_with(realpath($filepath) ?: $filepath, realpath($this->baseDir) ?: $this->baseDir)) {
                    continue;
                }

                $filename = basename($filepath);
                $grabacion = $this->parsearNombreArchivo($filename, $filepath, $telefono);

                if (!$grabacion) {
                    continue;
                }

                // Filtrar por ventana de tiempo
                $fechaArchivo = Carbon::parse($grabacion['fechaInicio']);
                if ($fechaArchivo->between($desde, $hasta)) {
                    $grabaciones[] = $grabacion;
                }
            }
        }

        // Ordenar por fecha
        usort($grabaciones, fn ($a, $b) => strcmp($a['fechaInicio'], $b['fechaInicio']));

        Log::info('CecocoGrabacionesLocalService: grabaciones encontradas', [
            'telefono'    => $telefono,
            'total'       => count($grabaciones),
        ]);

        return [
            'grabaciones' => $grabaciones,
            'ventana'     => [
                'desde' => $desde->format('Y-m-d H:i:s'),
                'hasta' => $hasta->format('Y-m-d H:i:s'),
            ],
            'fuente' => 'local',
        ];
    }

    /**
     * Parsea el nombre del archivo para extraer metadatos.
     * Formato: {id}_0_{telefono} (RDSI)_1_{YYYYMMDD}_{HHMMSS}_xf_{dur}s.mp3
     */
    private function parsearNombreArchivo(string $filename, string $filepath, string $telefono): ?array
    {
        // Extraer fecha y hora: buscar patrón YYYYMMDD_HHMMSS en el nombre
        if (!preg_match('/_(\d{8})_(\d{6})_/', $filename, $m)) {
            return null;
        }

        try {
            $fechaInicio = Carbon::createFromFormat('Ymd His', $m[1] . ' ' . $m[2]);
        } catch (\Exception $e) {
            return null;
        }

        // Extraer duración en segundos
        $duracion = '';
        if (preg_match('/_(\d+)s\.mp3$/i', $filename, $dm)) {
            $seg = (int) $dm[1];
            $duracion = sprintf('%02d:%02d', intdiv($seg, 60), $seg % 60);
        }

        // Extraer nombre del operador desde el directorio padre
        $operador = basename(dirname($filepath));

        return [
            'tipo'          => 'bri',
            'nombreFichero' => $filename,
            'path'          => $filepath,
            'url'           => null, // se completa en el controller
            'fechaInicio'   => $fechaInicio->format('Y-m-d H:i:s'),
            'duracion'      => $duracion,
            'numero'        => $telefono,
            'operador'      => $operador,
            'fuente'        => 'local',
        ];
    }

    /**
     * Verifica que el path sea válido y esté dentro del directorio base.
     */
    public function validarPath(string $filepath): bool
    {
        $real    = realpath($filepath);
        $realBase = realpath($this->baseDir);

        if (!$real || !$realBase) {
            return false;
        }

        return str_starts_with($real, $realBase);
    }
}
