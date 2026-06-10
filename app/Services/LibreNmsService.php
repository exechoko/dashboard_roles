<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

class LibreNmsService
{
    /**
     * Última lectura de CPU del grupo de video, guardada por el comando
     * librenms:monitorear-cpu para que otros módulos (bot de Telegram) la
     * consulten sin pegarle a LibreNMS.
     */
    public const CACHE_KEY_ULTIMO_USO = 'librenms.cpu_video.ultimo';

    private Client $client;

    private CookieJar $cookies;

    private ?string $csrfToken = null;

    private bool $sesionIniciada = false;

    public function __construct()
    {
        $this->cookies = new CookieJar();
        $this->client = new Client([
            'base_uri'        => rtrim(config('librenms.url'), '/') . '/',
            'cookies'         => $this->cookies,
            'timeout'         => (int) config('librenms.timeout', 30),
            'allow_redirects' => false,
            'http_errors'     => false,
        ]);
    }

    /**
     * Devuelve el uso actual de CPU de cada equipo del grupo, agregado por
     * dispositivo a partir de sus núcleos.
     *
     * @return array<int, array{device_id: int, hostname: string, nucleos: int, promedio: float, maximo: int}>
     */
    public function obtenerUsoCpuGrupo(?int $grupoId = null): array
    {
        $grupoId = $grupoId ?? (int) config('librenms.grupo_video');

        $this->iniciarSesion();

        $idsGrupo = $this->obtenerIdsDispositivosDeGrupo($grupoId);

        if (empty($idsGrupo)) {
            return [];
        }

        $filas = $this->obtenerFilasProcesadores();

        return self::agregarUsoPorDispositivo($filas, $idsGrupo);
    }

    private function iniciarSesion(): void
    {
        if ($this->sesionIniciada) {
            return;
        }

        $respuesta = $this->client->get('login');
        $html = (string) $respuesta->getBody();

        if (!preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
            throw new RuntimeException('LibreNMS: no se encontró el token CSRF en la página de login.');
        }

        $respuesta = $this->client->post('login', [
            'form_params' => [
                '_token'   => $m[1],
                'username' => config('librenms.user'),
                'password' => config('librenms.password'),
            ],
        ]);

        $destino = $respuesta->getHeaderLine('Location');

        if ($respuesta->getStatusCode() !== 302 || str_contains($destino, 'login')) {
            throw new RuntimeException('LibreNMS: login rechazado (revisar LIBRENMS_USER / LIBRENMS_PASSWORD).');
        }

        $this->sesionIniciada = true;
    }

    /**
     * Obtiene los IDs de dispositivos del grupo parseando la página de gráficos
     * filtrada por grupo (la misma URL que se usa en el navegador). De paso
     * captura el token CSRF de la sesión para los endpoints ajax.
     *
     * @return array<int, int>
     */
    private function obtenerIdsDispositivosDeGrupo(int $grupoId): array
    {
        $respuesta = $this->client->get('devices/graph/processor', [
            'query' => ['filter[groups.id][eq]' => $grupoId],
        ]);

        if ($respuesta->getStatusCode() !== 200) {
            throw new RuntimeException("LibreNMS: la página del grupo {$grupoId} devolvió HTTP {$respuesta->getStatusCode()}.");
        }

        $html = (string) $respuesta->getBody();

        if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
            $this->csrfToken = $m[1];
        }

        return self::extraerIdsDispositivos($html);
    }

    /**
     * Trae todas las filas de la tabla de procesadores (la que alimenta la
     * pantalla Health > Processors), con el uso actual de cada núcleo.
     *
     * @return array<int, array{device_hostname: string, processor_descr: string, processor_usage: string}>
     */
    private function obtenerFilasProcesadores(): array
    {
        if ($this->csrfToken === null) {
            throw new RuntimeException('LibreNMS: no hay token CSRF para consultar la tabla de procesadores.');
        }

        $respuesta = $this->client->post('ajax/table/processors', [
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CSRF-TOKEN'     => $this->csrfToken,
            ],
            'form_params' => [
                'current'      => 1,
                'rowCount'     => -1,
                'searchPhrase' => '',
            ],
        ]);

        if ($respuesta->getStatusCode() !== 200) {
            throw new RuntimeException("LibreNMS: la tabla de procesadores devolvió HTTP {$respuesta->getStatusCode()}.");
        }

        $datos = json_decode((string) $respuesta->getBody(), true);

        if (!is_array($datos) || !isset($datos['rows'])) {
            throw new RuntimeException('LibreNMS: respuesta inesperada de la tabla de procesadores.');
        }

        return $datos['rows'];
    }

    /**
     * Extrae los IDs de dispositivos de la página de gráficos de un grupo
     * (aparecen como graph.php?...&device=NNN).
     *
     * @return array<int, int>
     */
    public static function extraerIdsDispositivos(string $html): array
    {
        preg_match_all('/graph\.php\?[^"\']*?device=(\d+)/', $html, $coincidencias);

        $ids = array_map('intval', array_unique($coincidencias[1]));
        sort($ids);

        return $ids;
    }

    /**
     * Agrega las filas de procesadores (una por núcleo) en un resumen por
     * dispositivo, quedándose sólo con los dispositivos del grupo.
     *
     * @param array<int, array{device_hostname: string, processor_usage: string}> $filas
     * @param array<int, int> $idsGrupo
     * @return array<int, array{device_id: int, hostname: string, nucleos: int, promedio: float, maximo: int}>
     */
    public static function agregarUsoPorDispositivo(array $filas, array $idsGrupo): array
    {
        $porDispositivo = [];

        foreach ($filas as $fila) {
            $dato = self::parsearFilaProcesador($fila);

            if ($dato === null || !in_array($dato['device_id'], $idsGrupo, true)) {
                continue;
            }

            $porDispositivo[$dato['device_id']]['hostname'] = $dato['hostname'];
            $porDispositivo[$dato['device_id']]['usos'][]   = $dato['uso'];
        }

        $resumen = [];

        foreach ($porDispositivo as $deviceId => $datos) {
            $resumen[] = [
                'device_id' => $deviceId,
                'hostname'  => $datos['hostname'],
                'nucleos'   => count($datos['usos']),
                'promedio'  => round(array_sum($datos['usos']) / count($datos['usos']), 1),
                'maximo'    => max($datos['usos']),
            ];
        }

        usort($resumen, fn (array $a, array $b) => $b['promedio'] <=> $a['promedio']);

        return $resumen;
    }

    /**
     * Parsea una fila HTML de la tabla de procesadores: ID y hostname del enlace
     * al dispositivo, y el porcentaje de uso de la barra de progreso.
     *
     * @param array{device_hostname?: string, processor_usage?: string} $fila
     * @return array{device_id: int, hostname: string, uso: int}|null
     */
    public static function parsearFilaProcesador(array $fila): ?array
    {
        $celdaHost = $fila['device_hostname'] ?? '';
        $celdaUso  = $fila['processor_usage'] ?? '';

        if (!preg_match('#<a[^>]*/device/(\d+)[^>]*>\s*([^<]+?)\s*</a>#', $celdaHost, $host)) {
            return null;
        }

        if (!preg_match('/aria-valuenow="(\d+)"/', $celdaUso, $uso)) {
            return null;
        }

        return [
            'device_id' => (int) $host[1],
            'hostname'  => $host[2],
            'uso'       => (int) $uso[1],
        ];
    }
}
