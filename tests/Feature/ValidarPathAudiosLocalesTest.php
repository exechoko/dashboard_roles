<?php

namespace Tests\Feature;

use App\Services\CecocoGrabacionesLocalService;
use App\Services\CecocoModulacionesLocalService;
use Tests\TestCase;

class ValidarPathAudiosLocalesTest extends TestCase
{
    private string $raiz;

    private string $base;

    private string $hermana;

    protected function setUp(): void
    {
        parent::setUp();

        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'audios_test_' . uniqid();
        $this->base = $this->raiz . DIRECTORY_SEPARATOR . 'Audios Cecoco';
        $this->hermana = $this->base . '_backup';

        mkdir($this->base . DIRECTORY_SEPARATOR . '2026', 0777, true);
        mkdir($this->hermana, 0777, true);

        file_put_contents($this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'audio.mp3', 'x');
        file_put_contents($this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'AUDIO.WAV', 'x');
        file_put_contents($this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'notas.txt', 'x');
        file_put_contents($this->hermana . DIRECTORY_SEPARATOR . 'secreto.mp3', 'x');

        config([
            'grabador.recordings_path' => $this->base,
            'cecoco.recordings_path' => $this->base,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'audio.mp3',
            $this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'AUDIO.WAV',
            $this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'notas.txt',
            $this->hermana . DIRECTORY_SEPARATOR . 'secreto.mp3',
        ] as $archivo) {
            @unlink($archivo);
        }

        @rmdir($this->base . DIRECTORY_SEPARATOR . '2026');
        @rmdir($this->base);
        @rmdir($this->hermana);
        @rmdir($this->raiz);

        parent::tearDown();
    }

    /**
     * @dataProvider servicios
     */
    public function test_acepta_audios_dentro_de_la_base(string $clase): void
    {
        $servicio = new $clase();
        $dir = $this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR;

        $this->assertTrue($servicio->validarPath($dir . 'audio.mp3'));
        $this->assertTrue($servicio->validarPath($dir . 'AUDIO.WAV'));
    }

    /**
     * @dataProvider servicios
     */
    public function test_rechaza_una_carpeta_hermana_con_el_mismo_prefijo(string $clase): void
    {
        $servicio = new $clase();

        $this->assertFalse($servicio->validarPath($this->hermana . DIRECTORY_SEPARATOR . 'secreto.mp3'));
    }

    /**
     * @dataProvider servicios
     */
    public function test_rechaza_extensiones_que_no_son_audio(string $clase): void
    {
        $servicio = new $clase();

        $this->assertFalse($servicio->validarPath($this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . 'notas.txt'));
    }

    /**
     * @dataProvider servicios
     */
    public function test_rechaza_traversal_y_rutas_inexistentes(string $clase): void
    {
        $servicio = new $clase();
        $traversal = $this->base . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'Audios Cecoco_backup' . DIRECTORY_SEPARATOR . 'secreto.mp3';

        $this->assertFalse($servicio->validarPath($traversal));
        $this->assertFalse($servicio->validarPath($this->base . DIRECTORY_SEPARATOR . 'no-existe.mp3'));
        $this->assertFalse($servicio->validarPath($this->base));
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function servicios(): array
    {
        return [
            'modulaciones' => [CecocoModulacionesLocalService::class],
            'grabaciones' => [CecocoGrabacionesLocalService::class],
        ];
    }
}
