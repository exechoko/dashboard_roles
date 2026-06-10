<?php

namespace Tests\Feature;

use App\Services\GeocodificacionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodificacionServiceTest extends TestCase
{
    private function crearServicio(): GeocodificacionService
    {
        config([
            'services.nominatim.base_url' => 'http://nominatim.test',
            'services.nominatim.contexto' => ', Paraná',
            'services.google.geocoding_enabled' => false,
        ]);

        return new GeocodificacionService();
    }

    public function test_genera_candidato_sin_al_antes_de_la_altura(): void
    {
        $candidatos = $this->crearServicio()->generarCandidatos('Lavalleja al 1883');

        $this->assertContains('Lavalleja 1883', $candidatos);
    }

    public function test_genera_intersecciones_desde_formato_entre(): void
    {
        $candidatos = $this->crearServicio()->generarCandidatos('Moreno entre Salta y San Luis');

        $this->assertContains('Moreno y Salta', $candidatos);
        $this->assertContains('Moreno y San Luis', $candidatos);
    }

    public function test_genera_base_con_altura_desde_formato_entre(): void
    {
        $candidatos = $this->crearServicio()->generarCandidatos('Santos Tala 1172 entre Cabral y Pereyra');

        $this->assertContains('Santos Tala 1172', $candidatos);
        $this->assertContains('Santos Tala y Cabral', $candidatos);
    }

    public function test_quita_prefijo_calle(): void
    {
        $candidatos = $this->crearServicio()->generarCandidatos('calle San Juan 950');

        $this->assertContains('San Juan 950', $candidatos);
    }

    public function test_extrae_interseccion_de_narrativa_y_recorta_palabras_del_relato(): void
    {
        $narrativa = 'Se comunica un vecino informando que en calle Balbin y Larralde habria un incendio en un terreno baldio de la zona.';

        $candidatos = $this->crearServicio()->generarCandidatos($narrativa);

        $this->assertContains('Balbin y Larralde', $candidatos);
    }

    public function test_narrativa_sin_direccion_extraible_no_genera_candidatos(): void
    {
        $narrativa = 'Se comunica una ciudadana manifestando que escucho ruidos molestos y solicita presencia policial en la zona donde reside.';

        $this->assertSame([], $this->crearServicio()->generarCandidatos($narrativa));
    }

    public function test_geocodificar_nominatim_descarta_resultados_fuera_del_gran_parana(): void
    {
        $servicio = $this->crearServicio();

        Http::fake([
            'nominatim.test/search*' => Http::response([
                ['lat' => '-31.0000000', 'lon' => '-59.0000000'],
                ['lat' => '-31.7400000', 'lon' => '-60.4900000'],
            ]),
        ]);

        $resultado = $servicio->geocodificarNominatim('Una Calle 123');

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(-31.74, $resultado['lat'], 0.0001);
        $this->assertEqualsWithDelta(-60.49, $resultado['lng'], 0.0001);
    }

    public function test_resolver_con_nominatim_usa_variante_sin_al(): void
    {
        $servicio = $this->crearServicio();

        Http::fake(function (Request $request) {
            if ($request['q'] === 'Lavalleja 1883, Paraná') {
                return Http::response([['lat' => '-31.7332893', 'lon' => '-60.4996572']]);
            }
            return Http::response([]);
        });

        $resolucion = $servicio->resolverConNominatim('Lavalleja al 1883');

        $this->assertNotNull($resolucion);
        $this->assertSame('nominatim', $resolucion['fuente']);
        $this->assertSame('Lavalleja 1883', $resolucion['candidato']);
    }

    public function test_resolver_con_nominatim_calcula_interseccion_geometrica(): void
    {
        $servicio = $this->crearServicio();

        // La búsqueda por texto libre no resuelve intersecciones; las geometrías
        // de ambas calles se cruzan en (-60.49, -31.74).
        Http::fake(function (Request $request) {
            if (!isset($request['polygon_geojson'])) {
                return Http::response([]);
            }
            if (str_starts_with($request['q'], 'Falsa')) {
                return Http::response([[
                    'class'   => 'highway',
                    'geojson' => ['type' => 'LineString', 'coordinates' => [[-60.50, -31.74], [-60.48, -31.74]]],
                ]]);
            }
            if (str_starts_with($request['q'], 'Verdadera')) {
                return Http::response([[
                    'class'   => 'highway',
                    'geojson' => ['type' => 'LineString', 'coordinates' => [[-60.49, -31.75], [-60.49, -31.73]]],
                ]]);
            }
            return Http::response([]);
        });

        $resolucion = $servicio->resolverConNominatim('Falsa y Verdadera');

        $this->assertNotNull($resolucion);
        $this->assertSame('nominatim_interseccion', $resolucion['fuente']);
        $this->assertEqualsWithDelta(-31.74, $resolucion['lat'], 0.0001);
        $this->assertEqualsWithDelta(-60.49, $resolucion['lng'], 0.0001);
    }

    public function test_interseccion_geometrica_usa_vertices_cercanos_cuando_no_hay_cruce_exacto(): void
    {
        $servicio = $this->crearServicio();

        // Las polilíneas no se cortan pero sus extremos quedan a ~22 m (< 60 m).
        Http::fake(function (Request $request) {
            if (!isset($request['polygon_geojson'])) {
                return Http::response([]);
            }
            if (str_starts_with($request['q'], 'CalleA')) {
                return Http::response([[
                    'class'   => 'highway',
                    'geojson' => ['type' => 'LineString', 'coordinates' => [[-60.50, -31.74], [-60.4902, -31.74]]],
                ]]);
            }
            if (str_starts_with($request['q'], 'CalleB')) {
                return Http::response([[
                    'class'   => 'highway',
                    'geojson' => ['type' => 'LineString', 'coordinates' => [[-60.49, -31.7402], [-60.49, -31.75]]],
                ]]);
            }
            return Http::response([]);
        });

        $resolucion = $servicio->geocodificarInterseccion('CalleA', 'CalleB');

        $this->assertNotNull($resolucion);
        $this->assertEqualsWithDelta(-31.7401, $resolucion['lat'], 0.0002);
        $this->assertEqualsWithDelta(-60.4901, $resolucion['lng'], 0.0002);
    }

    public function test_interseccion_geometrica_rechaza_calles_lejanas(): void
    {
        $servicio = $this->crearServicio();

        Http::fake(function (Request $request) {
            if (!isset($request['polygon_geojson'])) {
                return Http::response([]);
            }
            if (str_starts_with($request['q'], 'CalleA')) {
                return Http::response([[
                    'class'   => 'highway',
                    'geojson' => ['type' => 'LineString', 'coordinates' => [[-60.50, -31.74], [-60.495, -31.74]]],
                ]]);
            }
            return Http::response([[
                'class'   => 'highway',
                'geojson' => ['type' => 'LineString', 'coordinates' => [[-60.40, -31.70], [-60.40, -31.71]]],
            ]]);
        });

        $this->assertNull($servicio->geocodificarInterseccion('CalleA', 'CalleB'));
    }
}
