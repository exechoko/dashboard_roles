<?php

namespace Tests\Unit;

use App\Services\GeneradorConfigDatos;
use Tests\TestCase;

class GeneradorConfigDatosTest extends TestCase
{
    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'landing_test_' . uniqid();
        mkdir($this->directorio . DIRECTORY_SEPARATOR . 'js', 0777, true);

        config([
            'landing.path' => $this->directorio,
            'landing.config_datos_js' => 'js/config-datos.js',
        ]);
    }

    protected function tearDown(): void
    {
        @array_map('unlink', glob($this->directorio . '/js/*'));
        @rmdir($this->directorio . '/js');
        @rmdir($this->directorio);

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        return [
            'anosServicio'        => 14,
            'funcionarios'        => 439,
            'camaras'             => 400,
            'moviles'             => 32,
            'motopatrullas'       => 32,
            'unidadesOperativas'  => 2,
            'llamadasPromedio'    => 590,
            'dispositivosDuales'  => 115,
            'usuariosBotonPanico' => 850,
            'meses2026'           => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            'armasPorMes'         => [9, 0, 9, 4, 8, 2],
            'vehiculosPorMes'     => [2, 1, 3, 3, 2, 1],
            'motosPorMes'         => [3, 4, 7, 4, 7, 6],
        ];
    }

    public function test_genera_el_archivo_con_los_valores(): void
    {
        $ruta = (new GeneradorConfigDatos())->generar($this->datos());

        $this->assertFileExists($ruta);
        $contenido = file_get_contents($ruta);

        $this->assertStringContainsString('funcionarios:        439', $contenido);
        $this->assertStringContainsString('camaras:             400', $contenido);
        $this->assertStringContainsString('armasPorMes:     [9, 0, 9, 4, 8, 2]', $contenido);
        $this->assertStringContainsString("meses2026:       ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']", $contenido);
    }

    public function test_mantiene_la_logica_de_totales_y_aplicar_datos(): void
    {
        $ruta = (new GeneradorConfigDatos())->generar($this->datos());
        $contenido = file_get_contents($ruta);

        $this->assertStringContainsString('DATOS.armasSecuestradas', $contenido);
        $this->assertStringContainsString('const _suma = arr => arr.reduce', $contenido);
        $this->assertStringContainsString('function aplicarDatos()', $contenido);
        $this->assertStringContainsString('[data-stat]', $contenido);
    }

    public function test_castea_valores_a_enteros_y_evita_inyeccion(): void
    {
        $datos = $this->datos();
        $datos['funcionarios'] = '500; alert(1)';

        $ruta = (new GeneradorConfigDatos())->generar($datos);
        $contenido = file_get_contents($ruta);

        $this->assertStringContainsString('funcionarios:        500', $contenido);
        $this->assertStringNotContainsString('alert(1)', $contenido);
    }

    public function test_soporta_cantidad_dinamica_de_meses(): void
    {
        $datos = $this->datos();
        $datos['meses2026']       = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'];
        $datos['armasPorMes']     = [9, 0, 9, 4, 8, 2, 5];
        $datos['vehiculosPorMes'] = [2, 1, 3, 3, 2, 1, 4];
        $datos['motosPorMes']     = [3, 4, 7, 4, 7, 6, 1];

        $ruta = (new GeneradorConfigDatos())->generar($datos);
        $contenido = file_get_contents($ruta);

        $this->assertStringContainsString("'Jun', 'Jul'", $contenido);
        $this->assertStringContainsString('armasPorMes:     [9, 0, 9, 4, 8, 2, 5]', $contenido);
        $this->assertStringContainsString('motosPorMes:     [3, 4, 7, 4, 7, 6, 1]', $contenido);
    }

    public function test_crea_backup_del_archivo_previo(): void
    {
        $generador = new GeneradorConfigDatos();
        $generador->generar($this->datos());
        $generador->generar($this->datos());

        $this->assertFileExists($generador->rutaArchivo() . '.bak');
    }
}
