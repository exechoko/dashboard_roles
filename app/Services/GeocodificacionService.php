<?php

namespace App\Services;

use App\Models\GeocodificacionDirecta;
use App\Models\GeocodificacionInversa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodificacionService
{
    private string $apiKey;
    private string $ciudadContexto;

    private const LIMITE_DIARIO_GOOGLE = 2000;

    private const PATRONES_INVALIDOS = [
        '/^[Dd]\.?\s*[Dd]\.?$/u',
        '/^(sin\s+datos?|s\/d|sd|n\/a|na|ninguna|g[eé]nero|domicilio|sin\s+domicilio|desconocida?|no\s+corresponde|sin\s+direcci[oó]n|particular|privado)$/iu',
    ];

    public function __construct()
    {
        $this->apiKey = env('API_GOOGLE', '');
        $this->ciudadContexto = ', Entre Ríos, Argentina';
    }

    /**
     * Determina si una dirección tiene formato válido para geocodificar.
     * Requiere un número de altura O una intersección ("Calle A y Calle B").
     */
    public function esDireccionValida(string $direccion): bool
    {
        $dir = trim($direccion);
        if ($dir === '' || $dir === '-') {
            return false;
        }

        foreach (self::PATRONES_INVALIDOS as $patron) {
            if (preg_match($patron, $dir)) {
                return false;
            }
        }

        $tieneNumero     = (bool) preg_match('/\d/', $dir);
        $esInterseccion  = (bool) preg_match('/\b\w+(?:\s+\w+)*\s+y\s+\w/iu', $dir);

        return $tieneNumero || $esInterseccion;
    }

    /**
     * Geocodifica una dirección de texto. Primero busca en la tabla geocodificacion_directa;
     * solo si no existe consulta la API de Google Maps.
     * El parámetro $nroExpediente se guarda para trazabilidad (un ejemplo de expediente
     * que usa esta dirección). Si el registro ya existía sin expediente, se actualiza.
     */
    public function geocodificar(string $direccion, ?string $nroExpediente = null): ?array
    {
        $direccion = trim($direccion);
        if (!$this->esDireccionValida($direccion)) {
            return null;
        }

        // Verificar en tabla antes de llamar a Google
        $cache = GeocodificacionDirecta::where('direccion_original', $direccion)->first();
        if ($cache) {
            // Rellenar nro_expediente si el registro no lo tenía
            if ($nroExpediente && !$cache->nro_expediente) {
                $cache->update(['nro_expediente' => $nroExpediente]);
            }
            if ($cache->latitud && $cache->longitud) {
                return ['lat' => $cache->latitud, 'lng' => $cache->longitud];
            }
            return null; // Ya se intentó y Google no la encontró
        }

        // Verificar límite diario ANTES de llamar a Google.
        // Si se alcanzó el límite NO se guarda nada en la tabla para poder reintentarlo mañana.
        if (!$this->hayDisponibilidadDiariaGoogle()) {
            Log::info('Geocodificación diferida por límite diario', ['direccion' => $direccion]);
            return null;
        }

        // Consultar Google e incrementar el contador diario
        $resultado = $this->consultarGoogle($direccion . $this->ciudadContexto);

        // Guardar en tabla: incluso null de Google, para no reintentar una dirección que no existe.
        // (Nunca llegamos aquí si el límite fue la causa del null.)
        GeocodificacionDirecta::create([
            'direccion_original'    => $direccion,
            'direccion_normalizada' => $resultado['formatted_address'] ?? null,
            'latitud'               => $resultado['lat'] ?? null,
            'longitud'              => $resultado['lng'] ?? null,
            'fuente'                => 'google',
            'nro_expediente'        => $nroExpediente,
        ]);

        if ($resultado && isset($resultado['lat'])) {
            return ['lat' => $resultado['lat'], 'lng' => $resultado['lng']];
        }

        return null;
    }

    /**
     * Devuelve true si aún quedan llamadas disponibles a Google para hoy.
     */
    public function hayDisponibilidadDiariaGoogle(): bool
    {
        $cacheKey = 'geocodificacion_google_daily_count:' . now()->format('Y-m-d');
        return (int) Cache::get($cacheKey, 0) < self::LIMITE_DIARIO_GOOGLE;
    }

    /**
     * Intenta extraer una dirección con numeración del texto de la descripción.
     * Busca patrones como "calle NombreCalle 1234" o "NombreCalle y OtraCalle".
     */
    public function extraerDireccionDeDescripcion(string $descripcion): ?string
    {
        if (empty($descripcion)) {
            return null;
        }

        // Patrón: "calle/s" seguido de nombre y número
        // Ej: "calle Casiano Calderon 134", "calles Republica de Siria y Casiano Calderon"
        $patrones = [
            // "calle NombreCalle 1234"
            '/(?:calle|calles|av\.?|avenida|bv\.?|boulevard|pasaje|pje\.?)\s+([A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\.]+?\s+\d{1,5})/iu',
            // "NombreCalle y OtraCalle" (intersección)
            '/([A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\.]+?\s+y\s+[A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\.]+)/iu',
            // "NombreCalle al 1234" o "NombreCalle Nº 1234"
            '/([A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\.]+?\s+(?:al|n[°ºo]\.?)\s*\d{1,5})/iu',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $descripcion, $matches)) {
                $direccionExtraida = trim($matches[1] ?? $matches[0]);
                // Limpiar: mínimo 5 caracteres, no solo números
                if (mb_strlen($direccionExtraida) >= 5 && !is_numeric($direccionExtraida)) {
                    return $direccionExtraida;
                }
            }
        }

        return null;
    }

    /**
     * Determina si una dirección tiene numeración (contiene al menos un número).
     */
    public function tieneNumeracion(string $direccion): bool
    {
        return preg_match('/\d/', $direccion) === 1;
    }

    /**
     * Geocodificación inversa: dadas coordenadas, retorna la dirección formateada.
     *
     * Usa la tabla `geocodificacion_inversa` como caché persistente. Primero
     * busca la coordenada (redondeada a 5 decimales ≈ 1.1 m); si no existe,
     * consulta Google Maps y guarda el resultado (incluso si es null, para
     * evitar reintentos sobre coordenadas sin resultado).
     *
     * @return string|null Dirección formateada o null si no se pudo resolver.
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        // Rango equivalente a redondeo a 5 decimales (~1.1 m). Se usa BETWEEN
        // en lugar de ROUND() para permitir el uso del índice compuesto
        // idx_geocod_inv_lat_lng.
        $eps = 0.000005;

        $row = GeocodificacionInversa::whereBetween('latitud',  [$lat - $eps, $lat + $eps])
            ->whereBetween('longitud', [$lng - $eps, $lng + $eps])
            ->first();

        if ($row) {
            return $row->direccion;
        }

        $direccion = $this->consultarReverseGoogle($lat, $lng);

        // Persistir también los null para no reintentar siempre sobre la misma coord.
        try {
            GeocodificacionInversa::create([
                'latitud'   => $lat,
                'longitud'  => $lng,
                'direccion' => $direccion,
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo persistir geocodificacion_inversa', [
                'error' => $e->getMessage(),
                'lat'   => $lat,
                'lng'   => $lng,
            ]);
        }

        return $direccion;
    }

    /**
     * Llama a Google Maps Geocoding para reverse-geocode (sin cache).
     */
    private function consultarReverseGoogle(float $lat, float $lng): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('API_GOOGLE no configurada para geocodificación inversa');
            return null;
        }

        try {
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'latlng'   => $lat . ',' . $lng,
                'key'      => $this->apiKey,
                'language' => 'es',
                'region'   => 'ar',
            ]);

            $response = @file_get_contents($url);
            if (!$response) {
                return null;
            }

            $data = json_decode($response);
            if ($data && ($data->status ?? '') === 'OK' && isset($data->results[0]->formatted_address)) {
                return (string) $data->results[0]->formatted_address;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error en geocodificación inversa Google', [
                'error' => $e->getMessage(),
                'lat'   => $lat,
                'lng'   => $lng,
            ]);
            return null;
        }
    }

    /**
     * Reverse-geocodifica un lote de coordenadas.
     *
     * Estrategia en 3 capas:
     *   1. Dedupe in-memory por clave "lat,lng" redondeada a 5 decimales.
     *   2. Prefetch masivo desde tabla `geocodificacion_inversa` (cache persistente).
     *   3. Para las que faltan, llamadas concurrentes a Google Maps (Guzzle Pool)
     *      y se insertan de vuelta en la tabla para próximas consultas.
     *
     * Crítico bajo túneles Cloudflare (timeout 100s): 500 puntos secuenciales
     * ~50s → con esta estrategia suele bajar a <5s (y a <1s cuando el cache DB
     * ya tiene la zona cubierta).
     *
     * @param array<int,array{0:float,1:float}> $pares Lista [[lat, lng], ...]
     * @return array<string,?string> Mapa "lat,lng" (5 decimales) → dirección.
     */
    public function reverseGeocodeBatch(array $pares, int $concurrencia = 20): array
    {
        $resultado  = [];
        $pendientes = []; // [clave => [lat, lng]]

        // 1) Deduplicar
        foreach ($pares as $p) {
            if (!isset($p[0], $p[1])) {
                continue;
            }
            $lat   = (float) $p[0];
            $lng   = (float) $p[1];
            $clave = sprintf('%.5f,%.5f', $lat, $lng);
            if (isset($resultado[$clave])) {
                continue;
            }
            $resultado[$clave]   = null;
            $pendientes[$clave]  = [$lat, $lng];
        }

        if (empty($pendientes)) {
            return $resultado;
        }

        // 2) Prefetch masivo desde geocodificacion_inversa.
        //    Una sola query con múltiples OR usando BETWEEN (aprovecha el
        //    índice compuesto idx_geocod_inv_lat_lng). Luego match exacto en PHP.
        $eps = 0.000005; // ≈ 1.1 m; equivalente a redondeo a 5 decimales
        $query = GeocodificacionInversa::query()->select('latitud', 'longitud', 'direccion');
        foreach ($pendientes as [$lat, $lng]) {
            $query->orWhere(function ($q) use ($lat, $lng, $eps) {
                $q->whereBetween('latitud',  [$lat - $eps, $lat + $eps])
                  ->whereBetween('longitud', [$lng - $eps, $lng + $eps]);
            });
        }
        $rows = $query->get();

        foreach ($rows as $row) {
            $claveRow = sprintf('%.5f,%.5f', (float) $row->latitud, (float) $row->longitud);
            if (array_key_exists($claveRow, $pendientes)) {
                $resultado[$claveRow] = $row->direccion;
                unset($pendientes[$claveRow]);
            }
        }

        $hitsDb = count($resultado) - count($pendientes);
        Log::info('Reverse-geocode batch: cache DB hit', [
            'pendientes_inicial' => count($resultado),
            'hits_db'            => $hitsDb,
            'a_consultar_google' => count($pendientes),
        ]);

        if (empty($pendientes)) {
            return $resultado;
        }

        if (empty($this->apiKey)) {
            Log::warning('API_GOOGLE no configurada para geocodificación inversa (batch)');
            return $resultado;
        }

        // 3) Google Maps en paralelo con Guzzle Pool
        $client = new \GuzzleHttp\Client([
            'base_uri'        => 'https://maps.googleapis.com',
            'timeout'         => 10,
            'connect_timeout' => 5,
            'http_errors'     => false,
        ]);

        $requests = function () use ($pendientes) {
            foreach ($pendientes as $clave => [$lat, $lng]) {
                $url = '/maps/api/geocode/json?' . http_build_query([
                    'latlng'   => $lat . ',' . $lng,
                    'key'      => $this->apiKey,
                    'language' => 'es',
                    'region'   => 'ar',
                ]);
                yield $clave => new \GuzzleHttp\Psr7\Request('GET', $url);
            }
        };

        $nuevos = []; // Para bulk-insert en DB

        $pool = new \GuzzleHttp\Pool($client, $requests(), [
            'concurrency' => $concurrencia,
            'fulfilled'   => function ($response, $clave) use (&$resultado, &$nuevos, $pendientes) {
                $body = (string) $response->getBody();
                $data = json_decode($body);
                $direccion = null;
                if ($data && ($data->status ?? '') === 'OK' && isset($data->results[0]->formatted_address)) {
                    $direccion = (string) $data->results[0]->formatted_address;
                }
                [$lat, $lng] = $pendientes[$clave];
                $resultado[$clave] = $direccion;
                $nuevos[] = [
                    'latitud'    => $lat,
                    'longitud'   => $lng,
                    'direccion'  => $direccion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            },
            'rejected' => function ($reason, $clave) use (&$resultado) {
                Log::error('Reverse-geocode batch rechazado', [
                    'clave'  => $clave,
                    'reason' => method_exists($reason, 'getMessage') ? $reason->getMessage() : (string) $reason,
                ]);
                $resultado[$clave] = null;
            },
        ]);

        $pool->promise()->wait();

        // Persistir los nuevos en DB (insert masivo; Eloquent timestamps manual)
        if (!empty($nuevos)) {
            try {
                GeocodificacionInversa::insert($nuevos);
            } catch (\Exception $e) {
                Log::warning('No se pudo bulk-insert en geocodificacion_inversa', [
                    'error' => $e->getMessage(),
                    'count' => count($nuevos),
                ]);
            }
        }

        return $resultado;
    }

    /**
     * Consulta la API de Google Maps Geocoding.
     * Respeta un límite diario de LIMITE_DIARIO_GOOGLE llamadas reales.
     */
    private function consultarGoogle(string $direccion): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('API_GOOGLE no configurada para geocodificación');
            return null;
        }

        // Incrementar contador diario (el check de límite ya fue hecho en geocodificar())
        $cacheKey = 'geocodificacion_google_daily_count:' . now()->format('Y-m-d');
        $contadorDiario = (int) Cache::get($cacheKey, 0);
        $ttl = now()->endOfDay()->diffInSeconds(now()) + 60;
        Cache::put($cacheKey, $contadorDiario + 1, $ttl);

        try {
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $direccion,
                'key' => $this->apiKey,
                'language' => 'es',
                'region' => 'ar',
                // Bounding box aproximado del Gran Paraná (SO a NE) para sesgar resultados
                'bounds' => '-31.860,-60.580|-31.680,-60.400',
                // Restringir estrictamente a la provincia de Entre Ríos, Argentina
                'components' => 'administrative_area:Entre Rios|country:AR',
            ]);

            $response = @file_get_contents($url);
            if (!$response) {
                return null;
            }

            $data = json_decode($response);
            if ($data && $data->status === 'OK' && isset($data->results[0])) {
                $result = $data->results[0];
                $lat = $result->geometry->location->lat;
                $lng = $result->geometry->location->lng;

                // Validación Geográfica Estricta (Gran Paraná: Oro Verde, San Benito, C. Avellaneda)
                // Sur: -31.90, Norte: -31.60 | Oeste: -60.60, Este: -60.30
                if ($lat >= -31.90 && $lat <= -31.60 && $lng >= -60.60 && $lng <= -60.30) {
                    return [
                        'lat' => $lat,
                        'lng' => $lng,
                        'formatted_address' => $result->formatted_address,
                    ];
                } else {
                    Log::warning("Dirección fuera del Gran Paraná descartada", ['dir' => $direccion, 'lat' => $lat, 'lng' => $lng]);
                    return null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error en geocodificación Google', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
