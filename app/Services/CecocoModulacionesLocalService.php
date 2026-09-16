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
    private int $toleranciaEmparejado;

    private const EXTENSIONES_AUDIO = ['mp3', 'wav', 'ogg', 'aac'];

    public function __construct()
    {
        $this->baseDir              = rtrim(config('grabador.recordings_path', 'G:\\Audios Cecoco'), '\\/');
        $this->marcadorTelefonia    = (string) config('grabador.marcador_telefonia', '(RDSI)');
        $this->toleranciaEmparejado = (int) config('grabador.tolerancia_emparejado', 5);
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
        if (empty($modulacionesGrabador)) {
            return $modulacionesGrabador;
        }

        // Sólo se escanean los minutos donde el grabador reportó modulaciones
        // (± 1 min por la tolerancia), no toda la ventana: así el costo escala con
        // la cantidad de modulaciones y no con el largo de la ventana (clave en
        // ventanas de varias horas sobre disco real).
        $minutos = [];
        foreach ($modulacionesGrabador as $m) {
            try {
                $t = Carbon::parse($m['fechaInicio']);
            } catch (\Exception $e) {
                continue;
            }
            foreach ([-1, 0, 1] as $offset) {
                $minutos[$t->copy()->addMinutes($offset)->format('Ymd_Hi')] = true;
            }
        }

        $archivos = $this->archivosEnMinutos(array_keys($minutos));
        if (empty($archivos)) {
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

            for ($delta = -$this->toleranciaEmparejado; $delta <= $this->toleranciaEmparejado; $delta++) {
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
        $prefijos = [];
        $cursor   = $desde->copy()->startOfMinute();

        while ($cursor->lte($hasta)) {
            $prefijos[] = $cursor->format('Ymd_Hi');
            $cursor->addMinute();
        }

        return $this->archivosEnMinutos($prefijos, $desde, $hasta);
    }

    /**
     * Escanea el disco sólo en los minutos indicados (prefijos "Ymd_Hi") y
     * devuelve los archivos de modulación, sin deduplicar.
     *
     * El escaneo se hace por DÍA, no por minuto: cada pasada tiene que recorrer
     * igual todas las carpetas de operador del mes (el layout es
     * {base}\YYYY\YYYY_MM\Operador\, sin subcarpeta por día), así que escanear
     * minuto por minuto repetía esa recorrida una vez por minuto. Con una ventana
     * de varias horas eso son cientos de recorridas del árbol del mes sobre un
     * disco de red, y es lo que agotaba el max_execution_time en producción.
     *
     * @param array<int, string> $prefijos  Minutos a escanear, formato "Ymd_Hi"
     * @return array<int, array<string, mixed>>
     */
    private function archivosEnMinutos(array $prefijos, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        if (!is_dir($this->baseDir)) {
            Log::debug('CecocoModulacionesLocalService: directorio base no existe', ['baseDir' => $this->baseDir]);

            return [];
        }

        $prefijos = array_unique($prefijos);

        $porDia = [];
        foreach ($prefijos as $prefijo) {
            $porDia[substr($prefijo, 0, 8)][] = $prefijo;
        }

        $resultado = [];

        foreach ($porDia as $dia => $minutosDelDia) {
            $delDia = $this->escanearDiaCacheado((string) $dia);

            foreach ($minutosDelDia as $prefijo) {
                foreach ($delDia[$prefijo] ?? [] as $modulacion) {
                    if ($desde && $hasta && !Carbon::parse($modulacion['fechaInicio'])->between($desde, $hasta)) {
                        continue;
                    }
                    $resultado[] = $modulacion;
                }
            }
        }

        return $resultado;
    }

    /**
     * Devuelve los archivos de un día agrupados por minuto ("Ymd_Hi"), cacheados
     * para que las búsquedas siguientes sobre el mismo día no vuelvan a tocar el disco.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function escanearDiaCacheado(string $dia): array
    {
        $cacheKey = 'mod_dia_' . md5($this->baseDir) . '_' . $dia;
        $cache    = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (is_array($cache)) {
            return $cache;
        }

        $inicio  = microtime(true);
        $escaneo = $this->escanearDia($dia);
        $tardo   = microtime(true) - $inicio;

        // Un escaneo incompleto (se acabó el presupuesto) se cachea poco tiempo:
        // sirve para no repetir la espera en la misma tanda de requests, pero deja
        // que más adelante se pueda completar.
        \Illuminate\Support\Facades\Cache::put(
            $cacheKey,
            $escaneo['porMinuto'],
            $escaneo['completo'] ? now()->addMinutes(10) : now()->addSeconds(60)
        );

        Log::info('CecocoModulacionesLocalService: escaneo de disco', [
            'dia'       => $dia,
            'segundos'  => round($tardo, 1),
            'minutos'   => count($escaneo['porMinuto']),
            'completo'  => $escaneo['completo'],
        ]);

        return $escaneo['porMinuto'];
    }

    /**
     * Recorre UNA vez las carpetas de operador del mes buscando los audios de un
     * día y los agrupa por minuto. Corta si se pasa del presupuesto de tiempo,
     * para que un disco de red lento nunca tumbe el request (las modulaciones sin
     * .mp3 local se sirven igual por el Replay Server).
     *
     * @return array{porMinuto: array<string, array<int, array<string, mixed>>>, completo: bool}
     */
    private function escanearDia(string $dia): array
    {
        $anio = substr($dia, 0, 4);
        $mes  = substr($dia, 4, 2);
        $dir  = $this->baseDir . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . $anio . '_' . $mes;

        if (!is_dir($dir)) {
            return ['porMinuto' => [], 'completo' => true];
        }

        $limite    = microtime(true) + (int) config('grabador.escaneo_disco_timeout', 25);
        $porMinuto = [];
        $completo  = true;

        $carpetas = glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [];

        foreach ($carpetas as $carpeta) {
            if (microtime(true) >= $limite) {
                $completo = false;
                Log::warning('CecocoModulacionesLocalService: escaneo de disco incompleto por tiempo', [
                    'dia' => $dia,
                    'dir' => $dir,
                ]);
                break;
            }

            foreach (glob($carpeta . DIRECTORY_SEPARATOR . '*_' . $dia . '_*', GLOB_NOSORT) ?: [] as $filepath) {
                $filename = basename($filepath);

                // Sólo audios y sólo modulaciones (excluir llamadas telefónicas).
                if (!in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::EXTENSIONES_AUDIO, true)) {
                    continue;
                }
                if ($this->marcadorTelefonia !== '' && str_contains($filename, $this->marcadorTelefonia)) {
                    continue;
                }

                $modulacion = $this->parsearNombreArchivo($filename, $filepath);
                if ($modulacion) {
                    $clave = Carbon::parse($modulacion['fechaInicio'])->format('Ymd_Hi');
                    $porMinuto[$clave][] = $modulacion;
                }
            }
        }

        return ['porMinuto' => $porMinuto, 'completo' => $completo];
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
