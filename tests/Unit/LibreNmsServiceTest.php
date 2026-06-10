<?php

namespace Tests\Unit;

use App\Services\LibreNmsService;
use PHPUnit\Framework\TestCase;

class LibreNmsServiceTest extends TestCase
{
    public function test_extrae_ids_de_dispositivos_de_la_pagina_del_grupo(): void
    {
        $html = '<img src="graph.php?height=110&amp;width=315&amp;id=0&amp;type=device_processor&amp;from=&amp;device=426">'
            . '<img src="graph.php?height=110&amp;width=315&amp;id=0&amp;type=device_processor&amp;from=&amp;device=445">'
            . '<img src="graph.php?height=110&amp;width=315&amp;id=0&amp;type=device_processor&amp;from=&amp;device=426">';

        $this->assertSame([426, 445], LibreNmsService::extraerIdsDispositivos($html));
    }

    public function test_extrae_ids_devuelve_vacio_sin_graficos(): void
    {
        $this->assertSame([], LibreNmsService::extraerIdsDispositivos('<html><body>Sin resultados</body></html>'));
    }

    public function test_parsea_fila_de_procesador(): void
    {
        $fila = $this->filaProcesador(434, 'CCTV-15', 70);

        $this->assertSame(
            ['device_id' => 434, 'hostname' => 'CCTV-15', 'uso' => 70],
            LibreNmsService::parsearFilaProcesador($fila)
        );
    }

    public function test_parsea_fila_invalida_devuelve_null(): void
    {
        $this->assertNull(LibreNmsService::parsearFilaProcesador([
            'device_hostname' => '<span>sin enlace</span>',
            'processor_usage' => '<div>sin barra</div>',
        ]));
    }

    public function test_agrega_uso_por_dispositivo_y_filtra_por_grupo(): void
    {
        $filas = [
            $this->filaProcesador(434, 'CCTV-15', 70),
            $this->filaProcesador(434, 'CCTV-15', 80),
            $this->filaProcesador(434, 'CCTV-15', 63),
            $this->filaProcesador(426, 'CCTV-01', 5),
            $this->filaProcesador(999, 'OTRO-EQUIPO', 95),
        ];

        $resumen = LibreNmsService::agregarUsoPorDispositivo($filas, [426, 434]);

        $this->assertCount(2, $resumen);
        $this->assertSame('CCTV-15', $resumen[0]['hostname']);
        $this->assertSame(71.0, $resumen[0]['promedio']);
        $this->assertSame(80, $resumen[0]['maximo']);
        $this->assertSame(3, $resumen[0]['nucleos']);
        $this->assertSame('CCTV-01', $resumen[1]['hostname']);
        $this->assertSame(5.0, $resumen[1]['promedio']);
    }

    /**
     * Arma una fila como la que devuelve el endpoint ajax/table/processors de
     * LibreNMS: el hostname dentro de un enlace al dispositivo y el uso en la
     * barra de progreso.
     *
     * @return array{device_hostname: string, processor_descr: string, processor_usage: string}
     */
    private function filaProcesador(int $deviceId, string $hostname, int $uso): array
    {
        return [
            'device_hostname' => '<div><span><a class="device-link-up" href="http://172.40.20.113/device/' . $deviceId . '">'
                . "\n        {$hostname}\n    </a></span></div>",
            'processor_descr' => 'Processor',
            'processor_usage' => '<a href="http://172.40.20.113/graphs/type=processor_usage/id=1/"><div style="width:400px">'
                . '<div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="' . $uso . '"'
                . ' aria-valuemin="0" aria-valuemax="100" style="width:' . $uso . '%;"></div></div></div></a>',
        ];
    }
}
