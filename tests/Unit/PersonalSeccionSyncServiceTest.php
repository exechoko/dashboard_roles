<?php

namespace Tests\Unit;

use App\Models\PersonalSeccion;
use App\Services\Personal911ImportService;
use App\Services\PersonalSeccionSyncService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PersonalSeccionSyncServiceTest extends TestCase
{
    private const MAPA_FUNCION_LUGAR = [
        'Monitoreo V.G. G2' => 10,
        'Adm. Judiciales T1' => 7,
        'Licencia Excepcional' => 1,
        'Móviles G3' => 3,
    ];

    private const MAPA_LUGARES = [
        1 => 'División 911',
        3 => 'Sección Patrullas 911',
        7 => 'Sección Judiciales y Gestión de Calidad',
        10 => 'Sección Violencia de Género',
    ];

    public function test_funcion_con_lugar_conocido_se_clasifica_como_activo(): void
    {
        $resultado = PersonalSeccionSyncService::clasificar(
            'Monitoreo V.G. G2',
            self::MAPA_FUNCION_LUGAR,
            self::MAPA_LUGARES,
            false
        );

        $this->assertSame('activo', $resultado['estado']);
        $this->assertSame(10, $resultado['id_lugar']);
        $this->assertSame('Sección Violencia de Género', $resultado['seccion']);
    }

    public function test_funcion_neutra_se_clasifica_como_en_licencia_si_no_esta_de_baja(): void
    {
        $resultado = PersonalSeccionSyncService::clasificar(
            'Licencia Excepcional',
            self::MAPA_FUNCION_LUGAR,
            self::MAPA_LUGARES,
            false
        );

        $this->assertSame('en_licencia', $resultado['estado']);
    }

    public function test_funcion_neutra_con_baja_policial_se_clasifica_como_baja(): void
    {
        $resultado = PersonalSeccionSyncService::clasificar(
            'Licencia Excepcional',
            self::MAPA_FUNCION_LUGAR,
            self::MAPA_LUGARES,
            true
        );

        $this->assertSame('baja', $resultado['estado']);
        $this->assertSame(PersonalSeccion::MOTIVO_BAJA_POLICIAL, $resultado['motivo']);
    }

    public function test_funcion_que_cambia_a_otra_seccion_operativa_no_es_neutra(): void
    {
        $resultado = PersonalSeccionSyncService::clasificar(
            'Móviles G3',
            self::MAPA_FUNCION_LUGAR,
            self::MAPA_LUGARES,
            false
        );

        $this->assertSame('activo', $resultado['estado']);
        $this->assertSame('Sección Patrullas 911', $resultado['seccion']);
    }

    public function test_funcion_desconocida_no_neutra_se_clasifica_como_baja_por_cambio_de_seccion(): void
    {
        $resultado = PersonalSeccionSyncService::clasificar(
            'Asuntos Judiciales Oficial',
            self::MAPA_FUNCION_LUGAR,
            self::MAPA_LUGARES,
            false
        );

        $this->assertSame('baja', $resultado['estado']);
        $this->assertSame(PersonalSeccion::MOTIVO_CAMBIO_SECCION, $resultado['motivo']);
    }

    public function test_sincronizar_manualmente_respeta_el_throttle_sin_tocar_personal911(): void
    {
        Cache::forever(PersonalSeccionSyncService::CACHE_KEY_ULTIMA_SINCRONIZACION, now());

        $importService = $this->createMock(Personal911ImportService::class);
        $importService->expects($this->never())->method('importar');

        $resultado = (new PersonalSeccionSyncService())->sincronizarManualmente($importService);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Ya se sincronizó hace poco', $resultado['mensaje']);
    }
}
