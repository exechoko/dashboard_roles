<?php

namespace Tests\Feature;

use App\Models\DetalleExpedienteCecoco;
use App\Models\EventoCecoco;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TiempoRespuestaCecocoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_requiere_permiso_para_ver_los_datos(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->getJson(route('api.cecoco.tiempos-respuesta.datos', ['desde' => '2026-01-01', 'hasta' => '2026-01-07']))
            ->assertForbidden();
    }

    public function test_calcula_el_tiempo_de_respuesta_desde_el_timeline(): void
    {
        $usuario = $this->usuarioConPermiso();

        $evento = EventoCecoco::factory()->create([
            'fecha_hora' => '2026-01-03 11:49:11',
            'tipo_servicio' => 'ROBO',
        ]);

        DetalleExpedienteCecoco::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_consulta' => now(),
            'detalle_json' => [
                'timeline' => [
                    ['fecha_hora' => '03/01/2026 11:49:11', 'estado' => 'P1020'],
                    ['fecha_hora' => '03/01/2026 11:49:13', 'estado' => 'P1020, En desplazamiento'],
                    ['fecha_hora' => '03/01/2026 12:11:10', 'estado' => 'P1020, En atención'],
                    ['fecha_hora' => '03/01/2026 12:55:51', 'estado' => 'P1020, Fin atención'],
                ],
            ],
        ]);

        $respuesta = $this->actingAs($usuario)
            ->getJson(route('api.cecoco.tiempos-respuesta.datos', ['desde' => '2026-01-01', 'hasta' => '2026-01-07']))
            ->assertOk()
            ->json();

        $this->assertSame(1, $respuesta['cobertura']);
        $this->assertEquals(22.0, $respuesta['promedio_minutos']);
        $this->assertSame('P1020', $respuesta['eventos_lentos'][0]['recurso']);
    }

    public function test_excluye_recursos_fijos_como_bases_y_despachos(): void
    {
        $usuario = $this->usuarioConPermiso();

        $evento = EventoCecoco::factory()->create([
            'fecha_hora' => '2026-01-03 08:00:00',
            'tipo_servicio' => 'AVISO',
        ]);

        DetalleExpedienteCecoco::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_consulta' => now(),
            'detalle_json' => [
                'timeline' => [
                    ['fecha_hora' => '03/01/2026 08:00:00', 'estado' => 'Base C08, En desplazamiento'],
                    ['fecha_hora' => '03/01/2026 10:40:00', 'estado' => 'Base C08, En atención'],
                ],
            ],
        ]);

        $respuesta = $this->actingAs($usuario)
            ->getJson(route('api.cecoco.tiempos-respuesta.datos', ['desde' => '2026-01-01', 'hasta' => '2026-01-07']))
            ->assertOk()
            ->json();

        $this->assertSame(0, $respuesta['cobertura']);
    }

    public function test_filtra_por_tipificacion(): void
    {
        $usuario = $this->usuarioConPermiso();

        $robo = EventoCecoco::factory()->create(['fecha_hora' => '2026-01-03 09:00:00', 'tipo_servicio' => 'ROBO']);
        $hurto = EventoCecoco::factory()->create(['fecha_hora' => '2026-01-03 09:00:00', 'tipo_servicio' => 'HURTO']);

        foreach ([$robo, $hurto] as $evento) {
            DetalleExpedienteCecoco::create([
                'evento_cecoco_id' => $evento->id,
                'nro_expediente' => $evento->nro_expediente,
                'fecha_consulta' => now(),
                'detalle_json' => [
                    'timeline' => [
                        ['fecha_hora' => '03/01/2026 09:00:00', 'estado' => 'P100, En desplazamiento'],
                        ['fecha_hora' => '03/01/2026 09:10:00', 'estado' => 'P100, En atención'],
                    ],
                ],
            ]);
        }

        $respuesta = $this->actingAs($usuario)
            ->getJson(route('api.cecoco.tiempos-respuesta.datos', [
                'desde' => '2026-01-01',
                'hasta' => '2026-01-07',
                'tipos' => ['ROBO'],
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $respuesta['cobertura']);
    }

    private function usuarioConPermiso(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(Permission::findOrCreate('ver-tiempos-respuesta-cecoco', 'web'));

        return $usuario;
    }
}
