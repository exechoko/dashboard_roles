<?php

namespace Tests\Unit;

use App\Models\Personal;
use App\Models\PersonalLicencia;
use App\Services\Personal911ImportService;
use Carbon\Carbon;
use Tests\TestCase;

class Personal911ImportServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    /**
     * @dataProvider observacionesChalecos
     */
    public function test_extrae_datos_de_chalecos_en_formatos_existentes(string $observacion, array $esperado): void
    {
        $resultado = (new Personal911ImportService())->extraerChaleco($observacion);

        $this->assertNotNull($resultado);
        foreach ($esperado as $campo => $valor) {
            $this->assertSame($valor, $resultado[$campo]);
        }
    }

    public function test_devuelve_null_si_no_puede_identificar_la_serie(): void
    {
        $resultado = (new Personal911ImportService())->extraerChaleco('Tiene chaleco sin datos identificatorios');

        $this->assertNull($resultado);
    }

    public function test_normaliza_la_misma_serie_para_detectar_chalecos_duplicados(): void
    {
        $service = new Personal911ImportService();

        $primero = $service->extraerChaleco('Chaleco Antibala ABPC Nro Serie: 16515 Talle: M');
        $segundo = $service->extraerChaleco('CHALECO BALISTICO ABPC SERIE N° 16515');

        $this->assertNotNull($primero);
        $this->assertNotNull($segundo);
        $this->assertSame($primero['numero_serie'], $segundo['numero_serie']);
    }

    public function test_acumula_renovaciones_consecutivas_hasta_la_fecha_de_consulta(): void
    {
        $licencias = collect([
            $this->crearLicencia([
                'tipo_licencia' => 'Lic. Anual Ordinaria',
                'fecha_inicio' => '2026-07-06',
                'fecha_fin' => '2026-07-21',
                'cantidad_dias' => 7,
            ]),
            $this->crearLicencia([
                'tipo_licencia' => 'Lic. Anual Ordinaria',
                'fecha_inicio' => '2026-07-22',
                'fecha_fin' => '2026-08-04',
                'cantidad_dias' => 13,
            ]),
        ]);

        $resumen = PersonalLicencia::resumenContinuidad($licencias, Carbon::parse('2026-07-28'));

        $this->assertNotNull($resumen);
        $this->assertSame('2026-07-06', $resumen['fecha_inicio']->toDateString());
        $this->assertSame('2026-08-04', $resumen['fecha_fin']->toDateString());
        $this->assertSame(20, $resumen['dias_otorgados']);
        $this->assertSame(23, $resumen['dias_transcurridos']);
        $this->assertCount(2, $resumen['licencias']);
    }

    public function test_no_acumula_licencias_separadas_por_reincorporacion(): void
    {
        $licencias = collect([
            $this->crearLicencia([
                'tipo_licencia' => 'Licencia anterior',
                'fecha_inicio' => '2026-07-01',
                'fecha_fin' => '2026-07-03',
                'cantidad_dias' => 3,
            ]),
            $this->crearLicencia([
                'tipo_licencia' => 'Licencia actual',
                'fecha_inicio' => '2026-07-05',
                'fecha_fin' => '2026-07-10',
                'cantidad_dias' => 6,
            ]),
        ]);

        $resumen = PersonalLicencia::resumenContinuidad($licencias, Carbon::parse('2026-07-06'));

        $this->assertNotNull($resumen);
        $this->assertSame('2026-07-05', $resumen['fecha_inicio']->toDateString());
        $this->assertSame(6, $resumen['dias_otorgados']);
        $this->assertSame(2, $resumen['dias_transcurridos']);
        $this->assertSame(['Licencia actual'], $resumen['tipos']->all());
    }

    public function test_identifica_una_licencia_informada_en_la_funcion_de_personal911(): void
    {
        $personal = new Personal();
        $personal->fill(['funcion_personal911' => 'Licencia Excepcional']);

        $this->assertTrue($personal->indicaLicenciaEnFuncion());

        $personal->funcion_personal911 = 'SubOf Gdia. Dep. G1';

        $this->assertFalse($personal->indicaLicenciaEnFuncion());
    }

    private function crearLicencia(array $atributos): PersonalLicencia
    {
        $licencia = new PersonalLicencia();
        $licencia->fill($atributos);

        return $licencia;
    }

    public static function observacionesChalecos(): array
    {
        return [
            'formato ABPC' => [
                'Chaleco Antibala ABPC Nro Serie: 16515 Talle: M',
                ['numero_serie' => '16515', 'marca' => 'ABPC', 'talle' => 'M'],
            ],
            'formato Seatle' => [
                'Chaleco Antibala SEATLE Nro Serie: 1038 Talle: S',
                ['numero_serie' => '1038', 'marca' => 'SEATLE', 'talle' => 'S'],
            ],
            'serie con modelo' => [
                'CHALECO BALISTICO INDUSTRIA SEATLE S.A, MODELO FORCE 10. SERIE N° 6668',
                ['numero_serie' => '6668', 'marca' => 'SEATLE', 'modelo' => 'FORCE 10'],
            ],
            'numero luego de chaleco' => [
                'CHALECO: N° 359 TALLE S-RB3 LOTE 24',
                ['numero_serie' => '359', 'talle' => 'S', 'nivel' => 'RB3', 'lote' => '24'],
            ],
            'numero antes del modelo' => [
                'Chaleco Antibala 1566 FORCE 13F',
                ['numero_serie' => '1566', 'modelo' => 'FORCE 13F'],
            ],
            'antibalas plural' => [
                'CHALECO ANTIBALAS ABPC 16297 TALLE S',
                ['numero_serie' => '16297', 'marca' => 'ABPC', 'talle' => 'S'],
            ],
            'prefijo duplicado' => [
                'chaleco force 13- nNº1748',
                ['numero_serie' => '1748', 'modelo' => 'FORCE 13'],
            ],
        ];
    }
}
