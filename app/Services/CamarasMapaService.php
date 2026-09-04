<?php

namespace App\Services;

use App\Models\Camara;
use Illuminate\Support\Facades\DB;

class CamarasMapaService
{
    /**
     * Cámaras activas con datos de sitio y tipo, listas para el mapa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paraMapa(): array
    {
        return Camara::select(
            'camaras.*',
            'sitio.*',
            'tipo_camara.tipo as tipo_camara',
            'tipo_camara.imagen as imagen',
            'tipo_camara.marca as marca',
            'tipo_camara.modelo as modelo',
            'tipo_camara.canales as canales',
            'destino.nombre as dependencia',
            DB::raw('sitio.nombre as sitio'),
            DB::raw('sitio.cartel as cartel'),
            DB::raw('sitio.latitud as latitud'),
            DB::raw('sitio.longitud as longitud'),
            DB::raw('camaras.id as numero'),
            DB::raw('camaras.nombre as titulo')
        )
            ->where('sitio.activo', 1)
            ->leftJoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->leftJoin('destino', 'sitio.destino_id', '=', 'destino.id')
            ->get()->toArray();
    }

    /**
     * GeoJSON de cámaras (Point) a partir del array de paraMapa().
     *
     * @param array<int, array<string, mixed>> $camaras
     * @return array<string, mixed>
     */
    public function geoJson(array $camaras): array
    {
        $features = [];

        foreach ($camaras as $camara) {
            $lat = $camara['latitud'] ?? null;
            $lng = $camara['longitud'] ?? null;
            if (!is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $lng, (float) $lat],
                ],
                'properties' => [
                    'id' => $camara['numero'],
                    'titulo' => $camara['titulo'],
                    'tipo_camara' => $camara['tipo_camara'],
                    'imagen' => $camara['imagen'],
                    'sitio' => $camara['sitio'],
                    'dependencia' => $camara['dependencia'],
                    'etapa' => $camara['etapa'] ?? null,
                    'fecha_instalacion' => $camara['fecha_instalacion'] ?? null,
                    'inteligencia' => $camara['inteligencia'] ?? null,
                    'marca' => $camara['marca'] ?? null,
                    'modelo' => $camara['modelo'] ?? null,
                    'nro_serie' => $camara['nro_serie'] ?? null,
                    'canales' => $camara['canales'] ?? 1,
                    'cartel' => (bool) ($camara['cartel'] ?? false),
                    'angulo' => $camara['angulo'] ?? 60,
                    'orientacion' => $camara['orientacion'] ?? 'norte',
                ],
            ];
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }
}
