<?php

namespace App\Services;

use App\Models\DispositivoEdificio;

class SnmpService
{
    public const CACHE_KEY_ESTADO = 'infraestructura.dispositivos.estado';

    private const OID_SYS_DESCR = '.1.3.6.1.2.1.1.1.0';

    // hrSystemUptime (host-resources-mib), NO sysUpTime (.1.3.6.1.2.1.1.3.0):
    // sysUpTime mide desde que arrancó el AGENTE SNMP, no el sistema — si se
    // reinicia el servicio SNMP sin reiniciar la PC (p.ej. al correr
    // habilitar-snmp.bat de nuevo) ese valor se resetea y queda mintiendo.
    // hrSystemUptime es el que coincide con lo que muestra "systeminfo".
    private const OID_SYS_UPTIME = '.1.3.6.1.2.1.25.1.1.0';
    private const OID_PROCESSOR_LOAD = '.1.3.6.1.2.1.25.3.3.1.2';
    private const OID_STORAGE_DESCR = '.1.3.6.1.2.1.25.2.3.1.3';
    private const OID_STORAGE_ALLOC_UNITS = '.1.3.6.1.2.1.25.2.3.1.4';
    private const OID_STORAGE_SIZE = '.1.3.6.1.2.1.25.2.3.1.5';
    private const OID_STORAGE_USED = '.1.3.6.1.2.1.25.2.3.1.6';

    private string $community;

    private int $snmpTimeoutUs;

    private int $snmpReintentos;

    private int $pingTimeoutMs;

    private int $pausaEntreOidsMs;

    public function __construct()
    {
        $this->community = (string) config('infraestructura.community', 'public');
        $this->snmpTimeoutUs = (int) config('infraestructura.snmp_timeout_us', 1500000);
        $this->snmpReintentos = (int) config('infraestructura.snmp_reintentos', 1);
        $this->pingTimeoutMs = (int) config('infraestructura.ping_timeout_ms', 700);
        $this->pausaEntreOidsMs = (int) config('infraestructura.pausa_entre_oids_ms', 300);
    }

    /**
     * @return array{alcanzable: bool, latencia_ms: int|null}
     */
    public function ping(string $ip): array
    {
        if (!self::esIpMonitoreable($ip)) {
            return ['alcanzable' => false, 'latencia_ms' => null];
        }

        $comando = sprintf('ping -n 1 -w %d %s', $this->pingTimeoutMs, escapeshellarg($ip));

        $inicio = microtime(true);
        exec($comando, $salida, $codigo);
        $latenciaMs = (int) round((microtime(true) - $inicio) * 1000);

        return [
            'alcanzable' => $codigo === 0,
            'latencia_ms' => $codigo === 0 ? $latenciaMs : null,
        ];
    }

    /**
     * Consulta CPU/RAM/disco por SNMP (host-resources-mib). Devuelve null si el
     * equipo no responde SNMP (agente no habilitado, community incorrecto, etc.).
     *
     * @return array{cpu_pct: float|null, cpu_modelo: string|null, sistema_operativo: string|null, uptime_segundos: int|null, ram_pct: float|null, ram_total_gb: float|null, ram_usado_gb: float|null, disco_pct: float|null, disco_total_gb: float|null, disco_usado_gb: float|null}|null
     */
    public function consultarMetricas(string $ip): ?array
    {
        if (!self::esIpMonitoreable($ip)) {
            return null;
        }

        // sysDescr es un GET puntual (no walk), confiable incluso encadenado
        // con otras consultas — trae modelo de CPU y sistema operativo en la
        // misma respuesta, sin gastar presupuesto de rate-limit en otro walk.
        $sysDescr = $this->getSeguro($ip, self::OID_SYS_DESCR);
        $cpuModelo = self::parsearModeloCpu($sysDescr);
        $sistemaOperativo = self::parsearSistemaOperativo($sysDescr);
        usleep($this->pausaEntreOidsMs * 1000);

        // hrSystemUptime: en equipos que no implementan host-resources-mib
        // (routers/switches) simplemente no responde y queda null, igual que
        // el resto de las métricas de esta función.
        $sysUpTime = $this->getSeguro($ip, self::OID_SYS_UPTIME);
        $uptimeSegundos = self::parsearUptimeSegundos($sysUpTime);
        usleep($this->pausaEntreOidsMs * 1000);

        $cargas = @snmp2_real_walk($ip, $this->community, self::OID_PROCESSOR_LOAD, $this->snmpTimeoutUs, $this->snmpReintentos);

        if ($cargas === false) {
            return null;
        }

        usleep($this->pausaEntreOidsMs * 1000);

        // El agente SNMP de Windows tolera mal walks sucesivos: un segundo
        // snmp2_real_walk() inmediatamente después de otro falla de forma
        // consistente (verificado empíricamente contra el Dell R420, incluso
        // con pausas de hasta 2s). En cambio, snmp2_get() puntual por índice
        // sí es confiable. Por eso acá solo se camina hrStorageDescr (una vez,
        // para descubrir qué fila es RAM y cuál es el disco del sistema) y el
        // resto de las columnas se piden por GET, solo para esas 1-2 filas.
        $descrCompleto = @snmp2_real_walk($ip, $this->community, self::OID_STORAGE_DESCR, $this->snmpTimeoutUs, $this->snmpReintentos) ?: [];
        $descr = self::filtrarFilasDeInteres($descrCompleto);

        $unit = [];
        $size = [];
        $used = [];

        foreach (array_keys($descr) as $oid) {
            $indice = substr($oid, strrpos($oid, '.') + 1);

            usleep($this->pausaEntreOidsMs * 1000);
            $unit[self::OID_STORAGE_ALLOC_UNITS . '.' . $indice] = $this->getSeguro($ip, self::OID_STORAGE_ALLOC_UNITS . '.' . $indice);

            usleep($this->pausaEntreOidsMs * 1000);
            $size[self::OID_STORAGE_SIZE . '.' . $indice] = $this->getSeguro($ip, self::OID_STORAGE_SIZE . '.' . $indice);

            usleep($this->pausaEntreOidsMs * 1000);
            $used[self::OID_STORAGE_USED . '.' . $indice] = $this->getSeguro($ip, self::OID_STORAGE_USED . '.' . $indice);
        }

        $tabla = self::parsearTablaStorage($descr, $unit, $size, $used);
        $recursos = self::extraerRamYDisco($tabla);

        return [
            'cpu_pct' => self::parsearCargaProcesadores($cargas),
            'cpu_modelo' => $cpuModelo,
            'sistema_operativo' => $sistemaOperativo,
            'uptime_segundos' => $uptimeSegundos,
            'ram_pct' => $recursos['ram']['pct'] ?? null,
            'ram_total_gb' => $recursos['ram']['total_gb'] ?? null,
            'ram_usado_gb' => $recursos['ram']['usado_gb'] ?? null,
            'disco_pct' => $recursos['disco']['pct'] ?? null,
            'disco_total_gb' => $recursos['disco']['total_gb'] ?? null,
            'disco_usado_gb' => $recursos['disco']['usado_gb'] ?? null,
        ];
    }

    /**
     * Releva un dispositivo completo: ping y, si responde y su tipo suele
     * exponer SNMP, también CPU/RAM/disco. Usado tanto por el comando
     * programado como por el refresco on-demand de la UI.
     *
     * @return array{id: int, nombre: string, ip: string, tipo: string, alcanzable: bool, latencia_ms: int|null, cpu_pct: float|null, cpu_modelo: string|null, sistema_operativo: string|null, uptime_segundos: int|null, ram_pct: float|null, ram_total_gb: float|null, ram_usado_gb: float|null, disco_pct: float|null, disco_total_gb: float|null, disco_usado_gb: float|null}
     */
    public function relevarDispositivo(DispositivoEdificio $dispositivo): array
    {
        $ping = $this->ping((string) $dispositivo->ip);

        $metricas = ($ping['alcanzable'] && self::esTipoConSnmp($dispositivo->tipo))
            ? $this->consultarMetricas($dispositivo->ip)
            : null;

        return [
            'id' => $dispositivo->id,
            'nombre' => $dispositivo->nombre,
            'ip' => (string) $dispositivo->ip,
            'tipo' => $dispositivo->tipo,
            'alcanzable' => $ping['alcanzable'],
            'latencia_ms' => $ping['latencia_ms'],
            'cpu_pct' => $metricas['cpu_pct'] ?? null,
            'cpu_modelo' => $metricas['cpu_modelo'] ?? null,
            'sistema_operativo' => $metricas['sistema_operativo'] ?? null,
            'uptime_segundos' => $metricas['uptime_segundos'] ?? null,
            'ram_pct' => $metricas['ram_pct'] ?? null,
            'ram_total_gb' => $metricas['ram_total_gb'] ?? null,
            'ram_usado_gb' => $metricas['ram_usado_gb'] ?? null,
            'disco_pct' => $metricas['disco_pct'] ?? null,
            'disco_total_gb' => $metricas['disco_total_gb'] ?? null,
            'disco_usado_gb' => $metricas['disco_usado_gb'] ?? null,
        ];
    }

    /**
     * Umbrales de alerta configurados (config/infraestructura.php).
     *
     * @return array{cpu: int, ram: int, disco: int}
     */
    public static function umbralesConfigurados(): array
    {
        return [
            'cpu' => (int) config('infraestructura.umbral_cpu'),
            'ram' => (int) config('infraestructura.umbral_ram'),
            'disco' => (int) config('infraestructura.umbral_disco'),
        ];
    }

    /**
     * Si vale la pena intentar SNMP para este tipo de dispositivo (PCs,
     * servidores, routers/switches sí; cámaras y puestos, no — verificado que
     * no lo exponen).
     */
    public static function esTipoConSnmp(string $tipo): bool
    {
        return in_array($tipo, DispositivoEdificio::TIPOS_CON_SO, true)
            || in_array($tipo, DispositivoEdificio::TIPOS_CON_PUERTOS, true);
    }

    /**
     * De sysDescr ("Hardware: <modelo de CPU> - Software: <SO>") se queda solo
     * con la parte de hardware. Si no matchea ese formato (agente no-Windows,
     * o vacío), devuelve el sysDescr recortado tal cual, o null si no hay dato.
     */
    public static function parsearModeloCpu(string $sysDescr): ?string
    {
        $limpio = self::limpiarDescr($sysDescr);

        if ($limpio === '') {
            return null;
        }

        if (preg_match('/^Hardware:\s*(.+?)\s*-\s*Software:/i', $limpio, $m)) {
            return $m[1];
        }

        return $limpio;
    }

    /**
     * De sysDescr ("Hardware: <modelo de CPU> - Software: <SO>") se queda solo
     * con la parte de software. Si no matchea ese formato, devuelve null — a
     * diferencia de parsearModeloCpu(), acá no tiene sentido usar el sysDescr
     * completo como "sistema operativo".
     */
    public static function parsearSistemaOperativo(string $sysDescr): ?string
    {
        $limpio = self::limpiarDescr($sysDescr);

        if ($limpio === '') {
            return null;
        }

        if (preg_match('/-\s*Software:\s*(.+)$/i', $limpio, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * De hrSystemUptime (TimeTicks, centésimas de segundo desde que arrancó
     * el sistema operativo) obtiene los segundos. net-snmp devuelve algo como
     * "Timeticks: (233366396) 27 days, 0:47:43.96"; toma el número entre
     * paréntesis, que es el valor crudo.
     */
    public static function parsearUptimeSegundos(string $sysUpTime): ?int
    {
        $ticks = self::extraerEntero($sysUpTime);

        return $ticks !== null ? intdiv($ticks, 100) : null;
    }

    /**
     * Estado de un dispositivo que todavía no tiene ninguna lectura cacheada:
     * 'ip_invalida' si el dato cargado en dispositivos_edificio no sirve para
     * monitorear, 'pendiente' si es válido pero el comando aún no lo relevó.
     */
    public static function estadoSinLectura(?string $ip): string
    {
        return self::esIpMonitoreable($ip) ? 'pendiente' : 'ip_invalida';
    }

    /**
     * Valida que la IP tenga formato correcto antes de usarla en cualquier
     * llamada externa (exec/snmp). Descarta valores como "10.175.15.300".
     */
    public static function esIpMonitoreable(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * De la columna cruda hrStorageDescr (walk completo), se queda solo con
     * la fila de RAM física ("Physical Memory") y la del disco del sistema
     * ("C:\..."), descartando el resto (Virtual Memory, otras unidades) para
     * minimizar cuántos GET puntuales hace falta pedir después.
     *
     * @param array<string, string> $descr
     * @return array<string, string>
     */
    public static function filtrarFilasDeInteres(array $descr): array
    {
        $filtrado = [];
        $tieneRam = false;
        $tieneDisco = false;

        foreach ($descr as $oid => $valor) {
            $limpio = self::limpiarDescr((string) $valor);

            if (!$tieneRam && stripos($limpio, 'Physical Memory') !== false) {
                $filtrado[$oid] = $valor;
                $tieneRam = true;
                continue;
            }

            if (!$tieneDisco && stripos($limpio, 'C:') === 0) {
                $filtrado[$oid] = $valor;
                $tieneDisco = true;
            }
        }

        return $filtrado;
    }

    /**
     * Promedia los valores de hrProcessorLoad devueltos por un snmp*_real_walk
     * (uno por núcleo/hilo). Devuelve null si no hay lecturas numéricas.
     *
     * @param array<string, string> $varbinds
     */
    public static function parsearCargaProcesadores(array $varbinds): ?float
    {
        $valores = [];

        foreach ($varbinds as $valor) {
            if (preg_match('/-?\d+/', (string) $valor, $m)) {
                $valores[] = (int) $m[0];
            }
        }

        if (empty($valores)) {
            return null;
        }

        return round(array_sum($valores) / count($valores), 1);
    }

    /**
     * Combina las 4 columnas de hrStorageTable (descr/allocUnits/size/used) en
     * una tabla indexada por índice de fila, con tamaño y uso ya en bytes.
     *
     * @param array<string, string> $descr
     * @param array<string, string> $unit
     * @param array<string, string> $size
     * @param array<string, string> $used
     * @return array<int, array{descr: string, size_bytes: float, used_bytes: float, pct: float}>
     */
    public static function parsearTablaStorage(array $descr, array $unit, array $size, array $used): array
    {
        $unitPorIndice = self::porIndiceOid($unit);
        $sizePorIndice = self::porIndiceOid($size);
        $usedPorIndice = self::porIndiceOid($used);

        $tabla = [];

        foreach (self::porIndiceOid($descr) as $indice => $valor) {
            $unitBytes = self::extraerEntero($unitPorIndice[$indice] ?? null);
            $sizeUnidades = self::extraerEntero($sizePorIndice[$indice] ?? null);
            $usedUnidades = self::extraerEntero($usedPorIndice[$indice] ?? null);

            if ($unitBytes === null || $sizeUnidades === null) {
                continue;
            }

            $sizeBytes = $sizeUnidades * $unitBytes;
            $usedBytes = ($usedUnidades ?? 0) * $unitBytes;

            $tabla[(int) $indice] = [
                'descr' => self::limpiarDescr((string) $valor),
                'size_bytes' => (float) $sizeBytes,
                'used_bytes' => (float) $usedBytes,
                'pct' => $sizeBytes > 0 ? round($usedBytes / $sizeBytes * 100, 1) : 0.0,
            ];
        }

        return $tabla;
    }

    /**
     * De la tabla de storage ya parseada, ubica la entrada de RAM física
     * ("Physical Memory") y la del disco del sistema (unidad "C:\").
     *
     * @param array<int, array{descr: string, size_bytes: float, used_bytes: float, pct: float}> $tabla
     * @return array{ram: array{pct: float, total_gb: float, usado_gb: float}|null, disco: array{pct: float, total_gb: float, usado_gb: float}|null}
     */
    public static function extraerRamYDisco(array $tabla): array
    {
        $ram = null;
        $disco = null;

        foreach ($tabla as $fila) {
            if ($ram === null && stripos($fila['descr'], 'Physical Memory') !== false) {
                $ram = self::aGb($fila);
            }

            if ($disco === null && stripos($fila['descr'], 'C:') === 0) {
                $disco = self::aGb($fila);
            }
        }

        return ['ram' => $ram, 'disco' => $disco];
    }

    /**
     * Clasifica el estado visual de un dispositivo según su lectura y los
     * umbrales configurados. 'caido' siempre gana; entre las métricas
     * presentes, la primera que supera su umbral dispara 'alerta'.
     *
     * @param array{alcanzable: bool, cpu_pct: float|null, ram_pct: float|null, disco_pct: float|null} $lectura
     * @param array{cpu: int, ram: int, disco: int} $umbrales
     */
    public static function clasificarEstado(array $lectura, array $umbrales): string
    {
        if (!$lectura['alcanzable']) {
            return 'caido';
        }

        $metricas = [
            'cpu' => $lectura['cpu_pct'] ?? null,
            'ram' => $lectura['ram_pct'] ?? null,
            'disco' => $lectura['disco_pct'] ?? null,
        ];

        $hayDatos = false;

        foreach ($metricas as $clave => $valor) {
            if ($valor === null) {
                continue;
            }

            $hayDatos = true;

            if ($valor > $umbrales[$clave]) {
                return 'alerta';
            }
        }

        return $hayDatos ? 'ok' : 'sin_snmp';
    }

    private function getSeguro(string $ip, string $oid): string
    {
        return @snmp2_get($ip, $this->community, $oid, $this->snmpTimeoutUs, $this->snmpReintentos) ?: '';
    }

    private static function aGb(array $fila): array
    {
        return [
            'pct' => $fila['pct'],
            'total_gb' => round($fila['size_bytes'] / 1073741824, 2),
            'usado_gb' => round($fila['used_bytes'] / 1073741824, 2),
        ];
    }

    private static function limpiarDescr(string $valor): string
    {
        return trim(str_replace(['STRING: ', '"'], '', $valor));
    }

    private static function extraerEntero(?string $valor): ?int
    {
        if ($valor === null) {
            return null;
        }

        if (!preg_match('/-?\d+/', $valor, $m)) {
            return null;
        }

        return (int) $m[0];
    }

    /**
     * Re-clavea una columna de hrStorageTable (oid completo => valor) por su
     * índice de fila final, para poder cruzar las 4 columnas en O(1) por fila
     * en vez de rescanear cada una por cada fila de la tabla.
     *
     * @param array<string, string> $columna
     * @return array<string, string>
     */
    private static function porIndiceOid(array $columna): array
    {
        $porIndice = [];

        foreach ($columna as $oid => $valor) {
            $porIndice[substr($oid, strrpos($oid, '.') + 1)] = $valor;
        }

        return $porIndice;
    }
}
