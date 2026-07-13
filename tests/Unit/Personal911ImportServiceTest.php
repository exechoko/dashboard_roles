<?php

namespace Tests\Unit;

use App\Services\Personal911ImportService;
use PHPUnit\Framework\TestCase;

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
