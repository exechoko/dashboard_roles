<?php

namespace Tests\Unit;

use App\Services\CecocoModulacionesEscaneoService;
use App\Services\CecocoModulacionesLocalService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CecocoModulacionesEscaneoTest extends TestCase
{
    public function test_termina_proceso_bloqueado_y_conserva_checkpoint(): void
    {
        config(['cache.stores.modulaciones' => ['driver' => 'array']]);
        $token = str_repeat('a', 64);
        $checkpoint = ['checkpoint' => ['archivo' => 12], 'archivos' => [], 'completa' => false];
        Cache::store('modulaciones')->put('mod_scan:' . $token, $checkpoint, 60);
        $service = new class extends CecocoModulacionesEscaneoService {
            public ?Process $child = null;
            protected function crearProceso(string $token): Process
            {
                return $this->child = new Process([PHP_BINARY, '-r', 'sleep(30);']);
            }
        };
        $start = microtime(true);
        // A short remaining budget exercises the same supervisor without a ten-second test sleep.
        $result = $service->avanzar($token, $start + 0.5);
        $this->assertLessThan(3, microtime(true) - $start);
        $this->assertFalse($service->child->isRunning());
        $this->assertSame($checkpoint['checkpoint'], $result['checkpoint']);
        $this->assertFalse($result['paso_ok']);
    }

    public function test_continua_un_checkpoint_y_excluye_telefonia_y_archivos_ajenos(): void
    {
        $root = sys_get_temp_dir() . '/mod_checkpoint_' . bin2hex(random_bytes(4));
        $dir = $root . '/2026/2026_10/OP';
        mkdir($dir, 0700, true);
        $files = ['1_0_GENERAL (TETRA)_1_20261002_100000_0f_3s.mp3',
            '2_0_GENERAL (TETRA)_1_20261002_100100_0f_3s.mp3',
            '3_0_(RDSI)_1_20261002_100200_0f_3s.mp3', 'notas.txt'];
        foreach ($files as $file) { file_put_contents($dir . '/' . $file, 'fixture'); }
        config(['grabador.recordings_path' => $root]);
        $job = ['ventana' => ['desde' => '2026-10-02 10:00:00', 'hasta' => '2026-10-02 11:00:00'],
            'checkpoint' => [], 'archivos' => [], 'completa' => false, 'agotada' => true, 'local' => true];
        $checkpoint = null;
        try {
            $service = new CecocoModulacionesLocalService();
            try {
                $service->escanearPaso($job, function ($next) use (&$checkpoint) {
                    $checkpoint = $next;
                    if (($next['checkpoint']['archivo'] ?? 0) >= 1) { throw new \RuntimeException('simulated interruption'); }
                });
            } catch (\RuntimeException $e) { $this->assertSame('simulated interruption', $e->getMessage()); }
            $this->assertNotNull($checkpoint);
            $service->escanearPaso($checkpoint, function ($next) use (&$job) { $job = $next; });
            $this->assertTrue($job['completa']);
            $this->assertCount(2, $job['archivos']);
        } finally {
            foreach ($files as $file) { unlink($dir . '/' . $file); }
            rmdir($dir); rmdir(dirname($dir)); rmdir($root . '/2026'); rmdir($root);
        }
    }
}
