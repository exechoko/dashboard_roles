<?php

namespace App\Services;

use App\Models\GeocodificacionDirecta;
use Illuminate\Support\Facades\Log;

class GeocodificacionService
{
    private string $apiKey;
    private string $ciudadContexto;

    public function __construct()
    {
        $this->apiKey = env('API_GOOGLE', '');
        $this->ciudadContexto = ', Paraná, Entre Ríos, Argentina';
    }

    /**
     * Geocodifica una dirección de texto. Primero busca en cache,
     * si no existe, consulta Google Maps Geocoding API.
     */
    public function geocodificar(string $direccion): ?array
    {
        $direccion = trim($direccion);
        if ($direccion === '' || $direccion === '-') {
            return null;
        }

        // Buscar en cache
        $cache = GeocodificacionDirecta::where('direccion_original', $direccion)->first();
        if ($cache) {
            if ($cache->latitud && $cache->longitud) {
                return ['lat' => $cache->latitud, 'lng' => $cache->longitud];
            }
            return null; // Ya se intentó y no se encontró
        }

        // Consultar Google
        $resultado = $this->consultarGoogle($direccion . $this->ciudadContexto);

        // Guardar en cache (incluso si no se encontró, para no reintentar)
        GeocodificacionDirecta::create([
            'direccion_original' => $direccion,
            'direccion_normalizada' => $resultado['formatted_address'] ?? null,
            'latitud' => $resultado['lat'] ?? null,
            'longitud' => $resultado['lng'] ?? null,
            'fuente' => 'google',
        ]);

        if ($resultado && isset($resultado['lat'])) {
            return ['lat' => $resultado['lat'], 'lng' => $resultado['lng']];
        }

        return null;
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
     * Consulta la API de Google Maps Geocoding.
     */
    private function consultarGoogle(string $direccion): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('API_GOOGLE no configurada para geocodificación');
            return null;
        }

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
                return [
                    'lat' => $result->geometry->location->lat,
                    'lng' => $result->geometry->location->lng,
                    'formatted_address' => $result->formatted_address,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error en geocodificación Google', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
