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
            'telefono' => $telefono,
            'total'    => count($grabaciones),
        ]);

        // Si no se encontró nada por número, buscar por ventana horaria ±2 min
        // usando patrones por minuto para evitar listar todo el mes
        if (empty($grabaciones)) {
            $desdeCorto = $fechaEvento->copy()->subMinutes(2);
            $hastaCorto = $fechaEvento->copy()->addMinutes(2);

            Log::info('CecocoGrabacionesLocalService: sin resultados por teléfono, buscando por ventana ±2 min', [
                'telefono' => $telefono,
                'desde'    => $desdeCorto->format('Y-m-d H:i:s'),
                'hasta'    => $hastaCorto->format('Y-m-d H:i:s'),
            ]);

            $grabaciones = $this->buscarPorVentana($desdeCorto, $hastaCorto);
        }

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
     * Fallback: busca archivos por ventana horaria usando patrones por minuto
     * (evita listar todo el directorio del mes).
     * Formato del timestamp en el nombre: _YYYYMMDD_HHMM??_
     */
    private function buscarPorVentana(Carbon $desde, Carbon $hasta): array
    {
        $grabaciones = [];
        $vistos      = [];
        $realBase    = realpath($this->baseDir) ?: $this->baseDir;

        $cursor = $desde->copy()->startOfMinute();

        while ($cursor->lte($hasta)) {
            $year      = $cursor->format('Y');
            $yearMonth = $cursor->format('Y_m');
            $dir       = $this->baseDir . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $yearMonth;

            if (is_dir($dir)) {
                // Patrón específico: solo archivos cuyo nombre contiene _YYYYMMDD_HHMM
                $prefijo  = $cursor->format('Ymd') . '_' . $cursor->format('Hi');
                $pattern  = $dir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*_' . $prefijo . '*';
                $archivos = glob($pattern, GLOB_NOSORT) ?: [];

                foreach ($archivos as $filepath) {
                    $real = realpath($filepath) ?: $filepath;
                    if (!str_starts_with($real, $realBase)) {
                        continue;
                    }

                    $filename = basename($filepath);

                    // Evitar duplicados si el mismo archivo coincide con varios minutos
                    if (isset($vistos[$filename])) {
                        continue;
                    }
                    $vistos[$filename] = true;

                    $grabacion = $this->parsearNombreArchivo($filename, $filepath);
                    if (!$grabacion) {
                        continue;
                    }

                    $fechaArchivo = Carbon::parse($grabacion['fechaInicio']);
                    if ($fechaArchivo->between($desde, $hasta)) {
                        $grabaciones[] = $grabacion;
                    }
                }
            }

            $cursor->addMinute();
        }

        usort($grabaciones, fn ($a, $b) => strcmp($a['fechaInicio'], $b['fechaInicio']));

        Log::info('CecocoGrabacionesLocalService: grabaciones encontradas por ventana horaria', [
            'total' => count($grabaciones),
        ]);

        return $grabaciones;
    }

    /**
     * Parsea el nombre del archivo para extraer metadatos.
     * Formato: {id}_0_{telefono o llamante} (RDSI)_1_{YYYYMMDD}_{HHMMSS}_xf_{dur}s.mp3
     *
     * @param string|null $telefono  Si es null, se extrae el llamante del propio nombre del archivo.
     */
    private function parsearNombreArchivo(string $filename, string $filepath, ?string $telefono = null): ?array
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

        // Si no se pasó teléfono, extraer el llamante del nombre del archivo
        // Formato esperado: {id}_0_{llamante}[ (RDSI)]_1_{fecha}
        if ($telefono === null) {
            if (preg_match('/_0_(.+?)(?:\s*\([^)]*\))?_1_\d{8}_/i', $filename, $cm)) {
                $telefono = trim($cm[1], " \t\n\r\0\x0B_");
            } else {
                $telefono = '';
            }
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
