<?php

namespace Tests\Unit;

use App\Services\CecocoModulacionesLocalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CecocoModulacionesLocalServiceTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'audios_test_' . uniqid();
        config(['grabador.recordings_path' => $this->baseDir]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->borrarDirectorio($this->baseDir);

        parent::tearDown();
    }

    public function test_empareja_las_filas_del_grabador_con_el_mp3_local_de_su_minuto(): void
    {
        $this->crearAudio('OPERADOR A', 'GENERAL (Grupo) (TETRA)', '20260912', '063205', 21);
        $this->crearAudio('OPERADOR B', 'GENERAL (Grupo) (TETRA)', '20260912', '094650', 8);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [
                ['fechaInicio' => '2026-09-12 06:32:05', 'duracion' => '00:21'],
                ['fechaInicio' => '2026-09-12 09:46:50', 'duracion' => '00:08'],
            ],
            Carbon::parse('2026-09-12 06:17:35'),
            Carbon::parse('2026-09-12 09:46:43')
        );

        $this->assertStringContainsString('063205', $modulaciones[0]['path']);
        $this->assertSame('local', $modulaciones[0]['fuenteAudio']);
        $this->assertStringContainsString('094650', $modulaciones[1]['path']);
    }

    public function test_empareja_dentro_de_la_tolerancia_de_segundos_y_no_fuera(): void
    {
        config(['grabador.tolerancia_emparejado' => 5]);

        $this->crearAudio('OPERADOR A', 'GENERAL (Grupo) (TETRA)', '20260912', '063207', 21);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [
                // 2 s de corrimiento: entra en la tolerancia.
                ['fechaInicio' => '2026-09-12 06:32:05', 'duracion' => '00:21'],
                // 30 s de corrimiento: no debe emparejar con nada.
                ['fechaInicio' => '2026-09-12 06:32:37', 'duracion' => '00:21'],
            ],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertArrayHasKey('path', $modulaciones[0]);
        $this->assertArrayNotHasKey('path', $modulaciones[1]);
    }

    public function test_prioriza_la_duracion_por_sobre_el_delta_de_tiempo_para_no_cruzar_canales(): void
    {
        config(['grabador.tolerancia_emparejado' => 5]);

        // Dos canales activos casi al mismo segundo: GRUPO 1 arranca justo en el
        // segundo de la fila del grabador (delta=0, el más "cercano") pero dura 9s;
        // GRUPO 2 arranca 3s después (dentro de la tolerancia) y dura 8s, exactamente
        // lo que reportó el grabador. La duración identifica mejor la transmisión
        // correcta que la cercanía en el tiempo — si el emparejado sólo mirara el
        // delta, elegiría por error el archivo de GRUPO 1 (de otro canal).
        $this->crearAudio('OPERADOR A', 'GRUPO 1 (TETRA)', '20260912', '063208', 9);
        $this->crearAudio('OPERADOR A', 'GRUPO 2 (TETRA)', '20260912', '063211', 8);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [['fechaInicio' => '2026-09-12 06:32:08', 'duracion' => '00:08']],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertStringContainsString('GRUPO 2', $modulaciones[0]['path']);
    }

    public function test_no_adivina_cuando_dos_canales_empatan_en_duracion_y_ofrece_ambos_candidatos(): void
    {
        config(['grabador.tolerancia_emparejado' => 5]);

        // Dos canales distintos, misma duración exacta (8s), ambos dentro de la
        // tolerancia de tiempo: no hay forma confiable de saber cuál es la
        // transmisión correcta -> no adivinar, ofrecer los dos para que el usuario
        // elija escuchando (en vez de dejarla sin audio local).
        $this->crearAudio('OPERADOR A', 'GRUPO 1 (TETRA)', '20260912', '063206', 8);
        $this->crearAudio('OPERADOR A', 'GRUPO 2 (TETRA)', '20260912', '063210', 8);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [['fechaInicio' => '2026-09-12 06:32:08', 'duracion' => '00:08']],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertArrayNotHasKey('path', $modulaciones[0]);
        $this->assertCount(2, $modulaciones[0]['candidatosAudio']);
        $canales = array_column($modulaciones[0]['candidatosAudio'], 'canal');
        sort($canales);
        $this->assertSame(['GRUPO 1 (TETRA)', 'GRUPO 2 (TETRA)'], $canales);
    }

    public function test_un_solo_candidato_por_canal_empata_pero_no_es_ambiguo_y_se_empareja(): void
    {
        config(['grabador.tolerancia_emparejado' => 5]);

        // Dos copias (dos operadores) del MISMO canal, misma duración: no es
        // ambigüedad real (es la misma transmisión), así que sí se empareja.
        $this->crearAudio('OPERADOR A', 'GRUPO 1 (TETRA)', '20260912', '063206', 8);
        $this->crearAudio('OPERADOR B', 'GRUPO 1 (TETRA)', '20260912', '063206', 8);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [['fechaInicio' => '2026-09-12 06:32:06', 'duracion' => '00:08']],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertArrayHasKey('path', $modulaciones[0]);
        $this->assertArrayNotHasKey('candidatosAudio', $modulaciones[0]);
    }

    public function test_no_empareja_una_fila_cuya_duracion_no_coincide(): void
    {
        $this->crearAudio('OPERADOR A', 'GENERAL (Grupo) (TETRA)', '20260912', '063205', 60);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [['fechaInicio' => '2026-09-12 06:32:05', 'duracion' => '00:05']],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertArrayNotHasKey('path', $modulaciones[0]);
    }

    public function test_ignora_las_llamadas_telefonicas_marcadas_como_rdsi(): void
    {
        $this->crearAudio('OPERADOR A', 'Linea 911 (RDSI)', '20260912', '063205', 21);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [['fechaInicio' => '2026-09-12 06:32:05', 'duracion' => '00:21']],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertArrayNotHasKey('path', $modulaciones[0]);
    }

    public function test_escanea_el_disco_una_sola_vez_por_dia_aunque_haya_muchos_minutos(): void
    {
        $filas = [];
        for ($minuto = 0; $minuto < 40; $minuto++) {
            $hora = Carbon::parse('2026-09-12 06:00:00')->addMinutes($minuto * 5);
            $this->crearAudio('OPERADOR A', 'GENERAL (Grupo) (TETRA)', '20260912', $hora->format('His'), 10);
            $filas[] = ['fechaInicio' => $hora->format('Y-m-d H:i:s'), 'duracion' => '00:10'];
        }

        $servicio = new CecocoModulacionesLocalService();

        $modulaciones = $servicio->emparejarConGrabador(
            $filas,
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 09:20:00')
        );

        $emparejadas = count(array_filter($modulaciones, fn ($m) => !empty($m['path'])));
        $this->assertSame(40, $emparejadas);

        // Un único escaneo por día, no uno por minuto.
        $this->assertTrue(Cache::has('mod_dia_' . md5($this->baseDir) . '_20260912'));

        // El día quedó cacheado entero: un segundo emparejado no vuelve al disco.
        $this->borrarDirectorio($this->baseDir . DIRECTORY_SEPARATOR . '2026');

        $deNuevo = $servicio->emparejarConGrabador(
            $filas,
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 09:20:00')
        );

        $this->assertSame(40, count(array_filter($deNuevo, fn ($m) => !empty($m['path']))));
    }

    public function test_la_busqueda_local_deduplica_las_copias_por_operador_y_respeta_la_ventana(): void
    {
        // La misma modulación grabada por dos operadores + una fuera de la ventana.
        $this->crearAudio('OPERADOR A', 'GENERAL (Grupo) (TETRA)', '20260912', '063205', 21);
        $this->crearAudio('OPERADOR B', 'GENERAL (Grupo) (TETRA)', '20260912', '063205', 21);
        $this->crearAudio('OPERADOR A', 'GENERAL (Grupo) (TETRA)', '20260912', '110000', 21);

        $resultado = (new CecocoModulacionesLocalService())->buscarModulaciones(
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertCount(1, $resultado['modulaciones']);
        $this->assertSame(2, $resultado['modulaciones'][0]['copias']);
        $this->assertSame('local', $resultado['fuente']);
    }

    public function test_devuelve_las_filas_intactas_si_el_directorio_base_no_existe(): void
    {
        config(['grabador.recordings_path' => $this->baseDir . '_inexistente']);

        $modulaciones = (new CecocoModulacionesLocalService())->emparejarConGrabador(
            [['fechaInicio' => '2026-09-12 06:32:05', 'duracion' => '00:21']],
            Carbon::parse('2026-09-12 06:00:00'),
            Carbon::parse('2026-09-12 07:00:00')
        );

        $this->assertCount(1, $modulaciones);
        $this->assertArrayNotHasKey('path', $modulaciones[0]);
    }

    private function crearAudio(string $operador, string $canal, string $fecha, string $hora, int $segundos): void
    {
        $anio = substr($fecha, 0, 4);
        $mes  = substr($fecha, 4, 2);
        $dir  = $this->baseDir . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . $anio . '_' . $mes
            . DIRECTORY_SEPARATOR . $operador;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $nombre = sprintf('%d_0_%s_1_%s_%s_0f_%ds.mp3', random_int(100000000, 999999999), $canal, $fecha, $hora, $segundos);
        file_put_contents($dir . DIRECTORY_SEPARATOR . $nombre, 'audio');
    }

    private function borrarDirectorio(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entrada) {
            if ($entrada === '.' || $entrada === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entrada;
            is_dir($path) ? $this->borrarDirectorio($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
