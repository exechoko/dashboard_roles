<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Consulta el GIS viewer de CECOCO para obtener el histórico de posiciones
 * GPS de un recurso móvil en un rango de fechas.
 *
 * Endpoint POST utilizado:
 *   /gisviewer/rest/position/resource/historicalGPSDataFromResource/
 *
 * Body (application/x-www-form-urlencoded):
 *   slanguage, idsubscriber, nameresource, maxRecords, minSecsBtwnRecords,
 *   startDate (YYYY-MM-DD HH:MM:SS), endDate (YYYY-MM-DD HH:MM:SS)
 *
 * Respuesta: {"error":null,"result":[Feature,...]} con cada Feature
 * conteniendo geometry.coordinates = [lng, lat] y properties.datetime/velocity.
 */
class CecocoGisService
{
    private GisAuthService $auth;

    private const HISTORICAL_POST_PATH = '/gisviewer/rest/position/resource/historicalGPSDataFromResource/';
    private const SEARCH_GET_PATH      = '/gisviewer/rest/position/resource/historicalResourcesWithGPSData';
    private const MAX_RECORDS          = 5000;
    private const MIN_SECS_BTW         = 1;
    // Último segmento del path del endpoint de búsqueda. Observado en la UI
    // del GIS; controla un umbral interno del servidor (cantidad mínima de
    // posiciones). Se mantiene igual al request capturado.
    private const SEARCH_MIN_POSITIONS = 4;

    public function __construct(GisAuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Obtiene el histórico de posiciones de un móvil desde el GIS viewer.
     *
     * @param string $recurso     Nombre del recurso en el GIS (ej: "Cria 916").
     * @param Carbon $fechaInicio Inicio del rango.
     * @param Carbon $fechaFin    Fin del rango.
     * @return array{recurso:string,posiciones:array<int,array{fecha:Carbon,lat:float,lng:float,velocidad:float,direccion:?string,orientacion:?string}>}
     *
     * @throws Exception
     */
    public function obtenerHistoricoMovil(string $recurso, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $recurso = trim($recurso);
        if ($recurso === '') {
            throw new Exception('Debe indicar el nombre del recurso.');
        }
        if ($fechaFin->lessThan($fechaInicio)) {
            throw new Exception('La fecha fin debe ser posterior a la fecha inicio.');
        }

        Log::info('CECOCO GIS: solicitud de histórico móvil', [
            'recurso' => $recurso,
            'desde'   => $fechaInicio->toDateTimeString(),
            'hasta'   => $fechaFin->toDateTimeString(),
        ]);

        [$client] = $this->auth->getAuthenticatedClient();
        $gisUrl   = $this->auth->getGisBaseUrl();

        [$status, $data] = $this->postHistorico($client, $gisUrl, $recurso, $fechaInicio, $fechaFin);

        if ($status !== 200) {
            Log::error('CECOCO GIS: status no-200', ['status' => $status, 'nameresource' => $recurso]);
            throw new RuntimeException("El GIS respondió HTTP {$status}.");
        }
        if (!empty($data['error'])) {
            throw new RuntimeException('Error reportado por el GIS: ' . $data['error']);
        }

        $features = $data['result'] ?? [];

        Log::info('CECOCO GIS: histórico recibido', [
            'cantidad'      => count($features),
            'recurso_usado' => $recurso,
        ]);

        if (empty($features)) {
            throw new RuntimeException(
                "El GIS no devolvió posiciones para el recurso \"{$recurso}\" en el rango indicado."
            );
        }

        return [
            'recurso'    => $recurso,
            'posiciones' => $this->parsearFeatures($features),
        ];
    }

    /**
     * Busca recursos disponibles en el GIS cuyo nombre contenga el texto
     * indicado, dentro del rango de fechas. Útil para autocompletado cuando
     * el usuario sólo conoce parte del nombre/ID del móvil.
     *
     * @return array<int,array{resourceName:string,alias:string}>
     */
    public function buscarRecursos(string $query, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        if ($fechaFin->lessThan($fechaInicio)) {
            throw new Exception('La fecha fin debe ser posterior a la fecha inicio.');
        }

        [$client] = $this->auth->getAuthenticatedClient();
        $gisUrl   = $this->auth->getGisBaseUrl();

        // Path: /historicalResourcesWithGPSData/{lang}/{idsub}/{start}/{end}/{partial}/{N}
        $url = $gisUrl . self::SEARCH_GET_PATH
            . '/en/0/'
            . rawurlencode($fechaInicio->format('Y-m-d H:i:s')) . '/'
            . rawurlencode($fechaFin->format('Y-m-d H:i:s')) . '/'
            . rawurlencode($query) . '/'
            . self::SEARCH_MIN_POSITIONS;

        Log::info('CECOCO GIS: buscando recursos', ['query' => $query, 'url' => $url]);

        $response = $client->get($url, [
            'headers' => [
                'Accept'           => 'application/json, text/javascript, */*; q=0.01',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer'          => $gisUrl . '/gisviewer/main/cecoco/historical?layerName=SCCMap:entrerios',
                'User-Agent'       => 'Mozilla/5.0',
            ],
            'allow_redirects' => true,
        ]);

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            Log::error('CECOCO GIS: búsqueda respondió no-JSON', ['body_preview' => substr($body, 0, 300)]);
            throw new RuntimeException('El GIS devolvió una respuesta no-JSON al buscar recursos.');
        }

        if (!empty($data['error'])) {
            throw new RuntimeException('Error del GIS al buscar recursos: ' . $data['error']);
        }

        $items = [];
        foreach ($data['result'] ?? [] as $row) {
            $name = (string) ($row['resourceName'] ?? '');
            if ($name === '') {
                continue;
            }
            $items[] = [
                'resourceName' => $name,
                'alias'        => (string) ($row['alias'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Ejecuta el POST al endpoint histórico del GIS.
     *
     * @return array{0:int,1:array} [status, decodedJson]
     */
    private function postHistorico($client, string $gisUrl, string $nameresource, Carbon $inicio, Carbon $fin): array
    {
        $response = $client->post($gisUrl . self::HISTORICAL_POST_PATH, [
            'form_params' => [
                'slanguage'          => 'en',
                'idsubscriber'       => '0',
                'nameresource'       => $nameresource,
                'maxRecords'         => self::MAX_RECORDS,
                'minSecsBtwnRecords' => self::MIN_SECS_BTW,
                'startDate'          => $inicio->format('Y-m-d H:i:s'),
                'endDate'            => $fin->format('Y-m-d H:i:s'),
            ],
            'headers' => [
                'Accept'           => 'application/json, text/javascript, */*; q=0.01',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer'          => $gisUrl . '/gisviewer/main/cecoco/historical?layerName=SCCMap:entrerios',
                'User-Agent'       => 'Mozilla/5.0',
            ],
            'allow_redirects' => true,
        ]);

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();
        $data   = json_decode($body, true);

        if (!is_array($data)) {
            Log::error('CECOCO GIS: respuesta no-JSON', ['body_preview' => substr($body, 0, 300)]);
            throw new RuntimeException('El GIS devolvió una respuesta no-JSON (¿sesión expirada?).');
        }

        return [$status, $data];
    }

    /**
     * Convierte los features GeoJSON del GIS en el formato interno.
     *
     * @param array<int,array> $features
     * @return array<int,array{fecha:Carbon,lat:float,lng:float,velocidad:float,direccion:?string,orientacion:?string}>
     */
    private function parsearFeatures(array $features): array
    {
        $out = [];
        foreach ($features as $f) {
            $coords = $f['geometry']['coordinates'] ?? null;
            $props  = $f['properties'] ?? [];
            if (!is_array($coords) || count($coords) < 2) {
                continue;
            }

            // GeoJSON → [lng, lat]
            $lng = (float) $coords[0];
            $lat = (float) $coords[1];

            $datetime = (string) ($props['datetime'] ?? '');
            // Normalizar "2026-04-21 00:00:43.0" → Carbon acepta directamente
            try {
                $fecha = Carbon::parse($datetime);
            } catch (\Exception $e) {
                Log::warning('CECOCO GIS: datetime inválido, feature omitido', ['datetime' => $datetime]);
                continue;
            }

            $out[] = [
                'fecha'       => $fecha,
                'lat'         => $lat,
                'lng'         => $lng,
                'velocidad'   => (float) ($props['velocity'] ?? 0),
                'direccion'   => null, // se resolverá por reverse-geocoding
                'orientacion' => $props['orientation'] ?? null,
            ];
        }

        // Ordenar por fecha ascendente por seguridad
        usort($out, fn($a, $b) => $a['fecha']->timestamp <=> $b['fecha']->timestamp);

        return $out;
    }
}
