<?php

namespace Tests\Unit;

use App\Services\EventoCecocoParser;
use PHPUnit\Framework\TestCase;

class EventoCecocoLimpiarDireccionTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function marcadoresSinCalle(): array
    {
        $casos = [
            'D.D', 'D.D.', 'DD', 'DD.-', 'D.D-', 'DD.', 'D.D.-', 'DD-', 'D D', 'D. D', '- D.D', 'D.D -',
            'FDD', 'dd. -', '.d.d.', 'D.D NO CERRAR', 'D.D PRUEBA', 'DD. NO CERRAR.-', 'D.D--',
            'Genero', 'GENERO-', 'genero.-', 'GENERO .-', 'Género', 'Area de Genero', 'Area  de Genero',
            'AREA DE VIOLENCIA', 'AREA VIOLENCIA', 'VIOLENCIA DE GENERO.', 'Sala de Violencia',
            'UNIDAD DE VIOLENCIA DE GENERO', 'UNIDAD DE VIOLENCIA DE GENERO Y ABUSO SEXUAL.-',
            'Unidad Fiscal de Violencia de Genero y Abuso Sexual.-', 'Unidad Fiscal de Genero',
            'Fiscalia de Genero.-', 'Fiscalia Violencia de Genero', 'Violencia familiar.-',
            'PROGRAMA VIOLENCIA FAMILIAR', 'Dispositivo Dual', '   ', '',
        ];

        return array_combine($casos, array_map(fn ($c) => [$c], $casos));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function direccionesConMarcador(): array
    {
        return [
            'prefijo con guion' => ['D.D-CALLE AMEGHINO', 'CALLE AMEGHINO'],
            'prefijo con punto' => ['dd. Ameghino al final', 'Ameghino al final'],
            'sufijo con guion' => ['PELLEGRINI Y BAVIO-DD', 'PELLEGRINI Y BAVIO'],
            'sufijo con espacio' => ['Panama Nº566 entre Catamarca y Santiago del Estero D.D', 'Panama Nº566 entre Catamarca y Santiago del Estero'],
            'genero entre parentesis' => ['España entre libertad e Italia (Genero)', 'España entre libertad e Italia'],
            'violencia de genero al final' => ['Tacuari 296 entre Soler y Sud America-violencia de Genero', 'Tacuari 296 entre Soler y Sud America'],
            'genero con barra' => ['GENERO / calle Cuba Nº 216', 'calle Cuba Nº 216'],
            'en proceso y marcador' => ['EN PROCESO-D.D-CALLE VICENTE DEL CASTILLO', 'CALLE VICENTE DEL CASTILLO'],
            'no cerrar y marcador' => ['NO CERRAR D.D- calle Santos Dominguez 110', 'calle Santos Dominguez 110'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function direccionesSinMarcadorOAmbiguas(): array
    {
        $casos = [
            'Córdoba 1234', 'Ameghino al final, detras del dispensario', 'Dispensario corrales',
            'pronunciamiento detras del dispensario', 'Predio FDD', 'Rosario del Tala 372, violencia de Genero 5 C',
            'Santiago Alfieri, Delegado Judicial de la Fiscalia de Violencia de Genero',
        ];

        return array_combine($casos, array_map(fn ($c) => [$c], $casos));
    }

    /**
     * @dataProvider marcadoresSinCalle
     */
    public function test_marcador_sin_calle_queda_null(string $direccion): void
    {
        $this->assertNull(EventoCecocoParser::limpiarDireccion($direccion));
    }

    /**
     * @dataProvider direccionesConMarcador
     */
    public function test_marcador_junto_a_calle_se_quita_y_conserva_la_calle(string $direccion, string $esperada): void
    {
        $this->assertSame($esperada, EventoCecocoParser::limpiarDireccion($direccion));
    }

    /**
     * @dataProvider direccionesSinMarcadorOAmbiguas
     */
    public function test_direccion_sin_marcador_o_ambigua_se_conserva(string $direccion): void
    {
        $this->assertSame($direccion, EventoCecocoParser::limpiarDireccion($direccion));
    }

    public function test_direccion_con_agresor_o_victima_queda_null(): void
    {
        $casos = [
            'D.D AGR_20 GONALEZ ANDRES', 'D.D-PEREZ GISELA VERONICA_VIC_34', 'SOSA MAXIMILIANO AGRESOR 84 D.D.',
            'Sr Comas Matias AGRESOR Nº 91 del D.D', 'D.D Morato Gisela (victima Nº 32)', 'Colissini Maria Laura VIC_78',
            'AGRESOR 109 D.D.', 'VICTIMA 109 D.D.', 'Zapata Vic Nº 56', 'Rios Matias (ER_AGR_68) - B. P',
        ];
        foreach ($casos as $direccion) {
            $this->assertNull(EventoCecocoParser::limpiarDireccion($direccion), $direccion);
        }
        $this->assertSame('CALLE AGR SEGISMUNDO SATULOVSKY', EventoCecocoParser::limpiarDireccion('CALLE AGR SEGISMUNDO SATULOVSKY'));
    }

    public function test_marcador_mas_relleno_sin_calle_queda_null(): void
    {
        foreach (['D.D CALLE', 'D.D-CALLE', 'PRUEBA D.D-', 'D.D en proceso', 'CONSULTA D.D', 'prueba genero'] as $direccion) {
            $this->assertNull(EventoCecocoParser::limpiarDireccion($direccion), $direccion);
        }
    }
}
