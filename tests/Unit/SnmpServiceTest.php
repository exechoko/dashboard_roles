<?php

namespace Tests\Unit;

use App\Services\SnmpService;
use PHPUnit\Framework\TestCase;

class SnmpServiceTest extends TestCase
{
    // ── esIpMonitoreable ────────────────────────────────────────────────

    public function test_ip_valida_es_monitoreable(): void
    {
        $this->assertTrue(SnmpService::esIpMonitoreable('193.169.1.246'));
    }

    public function test_ip_con_octeto_fuera_de_rango_no_es_monitoreable(): void
    {
        $this->assertFalse(SnmpService::esIpMonitoreable('10.175.15.300'));
    }

    public function test_ip_nula_no_es_monitoreable(): void
    {
        $this->assertFalse(SnmpService::esIpMonitoreable(null));
    }

    public function test_ip_vacia_no_es_monitoreable(): void
    {
        $this->assertFalse(SnmpService::esIpMonitoreable(''));
    }

    public function test_ip_con_texto_no_es_monitoreable(): void
    {
        $this->assertFalse(SnmpService::esIpMonitoreable('no-es-una-ip'));
    }

    // ── parsearModeloCpu ─────────────────────────────────────────────────

    public function test_extrae_el_modelo_de_cpu_del_sysdescr_de_windows(): void
    {
        $sysDescr = 'STRING: "Hardware: Intel64 Family 6 Model 62 Stepping 4 AT/AT COMPATIBLE'
            . ' - Software: Windows Version 6.3 (Build 17763 Multiprocessor Free)"';

        $this->assertSame(
            'Intel64 Family 6 Model 62 Stepping 4 AT/AT COMPATIBLE',
            SnmpService::parsearModeloCpu($sysDescr)
        );
    }

    public function test_sysdescr_sin_formato_hardware_software_se_devuelve_tal_cual(): void
    {
        $this->assertSame('Linux servidor 5.15.0', SnmpService::parsearModeloCpu('STRING: "Linux servidor 5.15.0"'));
    }

    public function test_sysdescr_vacio_devuelve_null(): void
    {
        $this->assertNull(SnmpService::parsearModeloCpu(''));
    }

    // ── parsearSistemaOperativo ──────────────────────────────────────────

    public function test_extrae_el_sistema_operativo_del_sysdescr_de_windows(): void
    {
        $sysDescr = 'STRING: "Hardware: Intel64 Family 6 Model 62 Stepping 4 AT/AT COMPATIBLE'
            . ' - Software: Windows Version 6.3 (Build 17763 Multiprocessor Free)"';

        $this->assertSame(
            'Windows Version 6.3 (Build 17763 Multiprocessor Free)',
            SnmpService::parsearSistemaOperativo($sysDescr)
        );
    }

    public function test_sysdescr_sin_formato_hardware_software_no_da_sistema_operativo(): void
    {
        $this->assertNull(SnmpService::parsearSistemaOperativo('STRING: "Linux servidor 5.15.0"'));
    }

    public function test_sysdescr_vacio_no_da_sistema_operativo(): void
    {
        $this->assertNull(SnmpService::parsearSistemaOperativo(''));
    }

    // ── parsearCargaProcesadores ────────────────────────────────────────

    public function test_promedia_la_carga_de_varios_nucleos(): void
    {
        $varbinds = $this->varbindsProcesador([5, 3, 6, 4, 14, 4, 14, 4, 18, 4, 15, 6]);

        $this->assertSame(8.1, SnmpService::parsearCargaProcesadores($varbinds));
    }

    public function test_carga_de_un_solo_nucleo(): void
    {
        $this->assertSame(42.0, SnmpService::parsearCargaProcesadores($this->varbindsProcesador([42])));
    }

    public function test_carga_vacia_devuelve_null(): void
    {
        $this->assertNull(SnmpService::parsearCargaProcesadores([]));
    }

    public function test_carga_con_varbinds_basura_devuelve_null(): void
    {
        $this->assertNull(SnmpService::parsearCargaProcesadores([
            'iso.3.6.1.2.1.25.3.3.1.2.4' => 'No Such Instance currently exists at this OID',
        ]));
    }

    // ── filtrarFilasDeInteres ────────────────────────────────────────────

    public function test_filtra_solo_ram_fisica_y_disco_c(): void
    {
        $descr = [
            'iso...1' => 'STRING: "C:\\ Label:  Serial Number 1a64e36a"',
            'iso...2' => 'STRING: "Virtual Memory"',
            'iso...3' => 'STRING: "Physical Memory"',
            'iso...4' => 'STRING: "D:\\ Label: Datos"',
        ];

        $filtrado = SnmpService::filtrarFilasDeInteres($descr);

        $this->assertSame([
            'iso...1' => 'STRING: "C:\\ Label:  Serial Number 1a64e36a"',
            'iso...3' => 'STRING: "Physical Memory"',
        ], $filtrado);
    }

    public function test_filtra_se_queda_con_la_primera_coincidencia_de_cada_una(): void
    {
        $descr = [
            'iso...1' => 'STRING: "Physical Memory"',
            'iso...2' => 'STRING: "Physical Memory"',
        ];

        $filtrado = SnmpService::filtrarFilasDeInteres($descr);

        $this->assertSame(['iso...1' => 'STRING: "Physical Memory"'], $filtrado);
    }

    public function test_filtra_sin_ram_ni_disco_devuelve_vacio(): void
    {
        $descr = ['iso...1' => 'STRING: "Virtual Memory"'];

        $this->assertSame([], SnmpService::filtrarFilasDeInteres($descr));
    }

    // ── parsearTablaStorage / extraerRamYDisco ─────────────────────────

    public function test_calcula_porcentaje_usado_de_ram_fisica(): void
    {
        $tabla = SnmpService::parsearTablaStorage(
            ['iso...3' => 'STRING: "Physical Memory"'],
            ['iso...3' => 'INTEGER: 1048576'],
            ['iso...3' => 'INTEGER: 32712'],
            ['iso...3' => 'INTEGER: 17428']
        );

        $recursos = SnmpService::extraerRamYDisco($tabla);

        $this->assertNotNull($recursos['ram']);
        $this->assertSame(53.3, $recursos['ram']['pct']);
        $this->assertSame(31.95, $recursos['ram']['total_gb']);
        $this->assertSame(17.02, $recursos['ram']['usado_gb']);
        $this->assertNull($recursos['disco']);
    }

    public function test_identifica_el_disco_c_entre_varias_unidades(): void
    {
        $tabla = SnmpService::parsearTablaStorage(
            [
                'iso...1' => 'STRING: "C:\\ Label:  Serial Number 1a64e36a"',
                'iso...2' => 'STRING: "Virtual Memory"',
                'iso...3' => 'STRING: "Physical Memory"',
            ],
            ['iso...1' => 'INTEGER: 4096', 'iso...2' => 'INTEGER: 1048576', 'iso...3' => 'INTEGER: 1048576'],
            ['iso...1' => 'INTEGER: 234032384', 'iso...2' => 'INTEGER: 38481', 'iso...3' => 'INTEGER: 32712'],
            ['iso...1' => 'INTEGER: 28464128', 'iso...2' => 'INTEGER: 21620', 'iso...3' => 'INTEGER: 17428']
        );

        $recursos = SnmpService::extraerRamYDisco($tabla);

        $this->assertNotNull($recursos['disco']);
        $this->assertSame(12.2, $recursos['disco']['pct']);
        $this->assertNotNull($recursos['ram']);
        $this->assertSame(53.3, $recursos['ram']['pct']);
    }

    public function test_indices_desalineados_entre_columnas_no_rompen_el_parseo(): void
    {
        $tabla = SnmpService::parsearTablaStorage(
            ['iso...1' => 'STRING: "Physical Memory"'],
            [], // sin allocUnits para ese índice
            ['iso...1' => 'INTEGER: 32712'],
            ['iso...1' => 'INTEGER: 17428']
        );

        $this->assertSame([], $tabla);
    }

    public function test_size_cero_no_produce_division_por_cero(): void
    {
        $tabla = SnmpService::parsearTablaStorage(
            ['iso...1' => 'STRING: "Physical Memory"'],
            ['iso...1' => 'INTEGER: 1024'],
            ['iso...1' => 'INTEGER: 0'],
            ['iso...1' => 'INTEGER: 0']
        );

        $this->assertSame(0.0, $tabla[1]['pct']);
    }

    // ── clasificarEstado ────────────────────────────────────────────────

    public function test_dispositivo_no_alcanzable_esta_caido_aunque_tenga_metricas(): void
    {
        $lectura = ['alcanzable' => false, 'cpu_pct' => 5.0, 'ram_pct' => 10.0, 'disco_pct' => 10.0];

        $this->assertSame('caido', SnmpService::clasificarEstado($lectura, $this->umbrales()));
    }

    public function test_metricas_bajo_el_umbral_estan_ok(): void
    {
        $lectura = ['alcanzable' => true, 'cpu_pct' => 20.0, 'ram_pct' => 50.0, 'disco_pct' => 30.0];

        $this->assertSame('ok', SnmpService::clasificarEstado($lectura, $this->umbrales()));
    }

    public function test_metrica_justo_en_el_umbral_no_alerta(): void
    {
        $lectura = ['alcanzable' => true, 'cpu_pct' => 85.0, 'ram_pct' => null, 'disco_pct' => null];

        $this->assertSame('ok', SnmpService::clasificarEstado($lectura, $this->umbrales()));
    }

    public function test_metrica_apenas_sobre_el_umbral_alerta(): void
    {
        $lectura = ['alcanzable' => true, 'cpu_pct' => 85.1, 'ram_pct' => null, 'disco_pct' => null];

        $this->assertSame('alerta', SnmpService::clasificarEstado($lectura, $this->umbrales()));
    }

    public function test_alcanzable_sin_ninguna_metrica_snmp_esta_sin_snmp(): void
    {
        $lectura = ['alcanzable' => true, 'cpu_pct' => null, 'ram_pct' => null, 'disco_pct' => null];

        $this->assertSame('sin_snmp', SnmpService::clasificarEstado($lectura, $this->umbrales()));
    }

    public function test_disco_sobre_umbral_alerta_aunque_cpu_este_bien(): void
    {
        $lectura = ['alcanzable' => true, 'cpu_pct' => 5.0, 'ram_pct' => 20.0, 'disco_pct' => 95.0];

        $this->assertSame('alerta', SnmpService::clasificarEstado($lectura, $this->umbrales()));
    }

    /**
     * @return array{cpu: int, ram: int, disco: int}
     */
    private function umbrales(): array
    {
        return ['cpu' => 85, 'ram' => 90, 'disco' => 90];
    }

    /**
     * @param array<int, int> $cargas
     * @return array<string, string>
     */
    private function varbindsProcesador(array $cargas): array
    {
        $varbinds = [];

        foreach ($cargas as $i => $carga) {
            $varbinds['iso.3.6.1.2.1.25.3.3.1.2.' . ($i + 1)] = 'INTEGER: ' . $carga . ' %';
        }

        return $varbinds;
    }
}
