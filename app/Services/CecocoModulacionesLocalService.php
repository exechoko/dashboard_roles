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

    /** Tolerancia (en segundos) al emparejar una fila del grabador con un archivo local. */
    private const TOLERANCIA_EMPAREJADO = 3;

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

        foreach ($this->archivosEnVentana($desde, $hasta) as $modulacion) {
            // CECOCO graba la misma modulación una vez por operador que la escucha;
            // se colapsan las copias por inicio + duración + canal (sólo difieren
            // en el id inicial y en la carpeta del operador).
            $clave = $modulacion['fechaInicio'] . '|' . $modulacion['duracion'] . '|' . $modulacion['canal'];

            if (isset($modulaciones[$clave])) {
                $modulaciones[$clave]['copias']++;
                if (!in_array($modulacion['operador'], $modulaciones[$clave]['operadores'], true)) {
                    $modulaciones[$clave]['operadores'][] = $modulacion['operador'];
                }
            } else {
                $modulacion['copias']     = 1;
                $modulacion['operadores'] = $modulacion['operador'] !== '' ? [$modulacion['operador']] : [];
                $modulaciones[$clave]     = $modulacion;
            }
        }

        $modulaciones = array_values($modulaciones);
        usort($modulaciones, fn ($a, $b) => strcmp($a['fechaInicio'], $b['fechaInicio']));

        Log::info('CecocoModulacionesLocalService: modulaciones encontradas', [
            'desde'  => $desde->format('Y-m-d H:i:s'),
            'hasta'  => $hasta->format('Y-m-d H:i:s'),
            'unicas' => count($modulaciones),
            'copias' => array_sum(array_column($modulaciones, 'copias')),
        ]);

        return [
            'modulaciones' => $modulaciones,
            'ventana'      => $this->ventana($desde, $hasta),
            'fuente'       => 'local',
        ];
    }

    /**
     * Empareja las modulaciones que devuelve el grabador (una fila por modulación
     * real) con los .mp3 del backup local, por hora de inicio (± tolerancia) y
     * duración. Los ids no sirven para cruzar: el rowid del grabador no coincide
     * con el número inicial del nombre del archivo (que además cambia por copia).
     * Cuando hay coincidencia se agrega 'path' para servir el .mp3 local en lugar
     * del WAV del Replay Server.
     *
     * @param array<int, array<string, mixed>> $modulacionesGrabador
     * @return array<int, array<string, mixed>>
     */
    public function emparejarConGrabador(array $modulacionesGrabador, Carbon $desde, Carbon $hasta): array
    {
        $archivos = $this->archivosEnVentana($desde, $hasta);
        if (empty($archivos) || empty($modulacionesGrabador)) {
            return $modulacionesGrabador;
        }

        $porSegundo = [];
        foreach ($archivos as $i => $archivo) {
            $porSegundo[Carbon::parse($archivo['fechaInicio'])->getTimestamp()][] = $i;
        }

        $emparejadas = 0;

        foreach ($modulacionesGrabador as &$m) {
            try {
                $ts = Carbon::parse($m['fechaInicio'])->getTimestamp();
            } catch (\Exception $e) {
                continue;
            }

            $durGrabador = $this->duracionASegundos((string) ($m['duracion'] ?? ''));
            $mejor       = null;
            $mejorDelta  = PHP_INT_MAX;

            for ($delta = -self::TOLERANCIA_EMPAREJADO; $delta <= self::TOLERANCIA_EMPAREJADO; $delta++) {
                foreach ($porSegundo[$ts + $delta] ?? [] as $i) {
                    $durArchivo = $this->duracionASegundos($archivos[$i]['duracion']);
                    if ($durGrabador !== null && $durArchivo !== null && abs($durGrabador - $durArchivo) > 2) {
                        continue;
                    }
                    if (abs($delta) < $mejorDelta) {
                        $mejorDelta = abs($delta);
                        $mejor      = $archivos[$i];
                    }
                }
            }

            if ($mejor !== null) {
                $m['path']        = $mejor['path'];
                $m['fuenteAudio'] = 'local';
                if (empty($m['recurso']) && $mejor['recurso'] !== '') {
                    $m['recurso'] = $mejor['recurso'];
                }
                $emparejadas++;
            }
        }
        unset($m);

        Log::info('CecocoModulacionesLocalService: emparejado con grabador', [
            'grabador'    => count($modulacionesGrabador),
            'emparejadas' => $emparejadas,
        ]);

        return $modulacionesGrabador;
    }

    /**
     * Escanea el disco y devuelve todos los archivos de modulación de la ventana,
     * sin deduplicar (una entrada por archivo/copia).
     *
     * @return array<int, array<string, mixed>>
     */
    private function archivosEnVentana(Carbon $desde, Carbon $hasta): array
    {
        $resultado = [];
        $vistos    = [];
        $realBase  = realpath($this->baseDir) ?: $this->baseDir;

        if (!is_dir($this->baseDir)) {
            Log::debug('CecocoModulacionesLocalService: directorio base no existe', ['baseDir' => $this->baseDir]);

            return [];
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

                    if (!Carbon::parse($modulacion['fechaInicio'])->between($desde, $hasta)) {
                        continue;
                    }

                    $resultado[] = $modulacion;
                }
            }

            $cursor->addMinute();
        }

        return $resultado;
    }

    /**
     * Convierte una duración ("21", "00:21", "0:00:21") a segundos.
     */
    private function duracionASegundos(string $duracion): ?int
    {
        $duracion = trim($duracion);

        if ($duracion === '') {
            return null;
        }
        if (ctype_digit($duracion)) {
            return (int) $duracion;
        }
        if (preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})$/', $duracion, $m)) {
            return ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + (int) $m[3];
        }

        return null;
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
            'recurso'     => $this->extraerRecurso($canal),
            'operador'    => basename(dirname($filepath)),
            'path'        => $filepath,
            'fuente'      => 'local',
        ];
    }

    /**
     * Extrae el recurso/unidad de la etiqueta del canal: lo que está entre corchetes.
     * Ej: "GENERAL (Grupo) [Cria 904 (M2230904)] (TETRA)" → "Cria 904 (M2230904)".
     */
    private function extraerRecurso(string $canal): string
    {
        if (preg_match('/\[([^\]]+)\]/', $canal, $m)) {
            return trim($m[1]);
        }

        return '';
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
