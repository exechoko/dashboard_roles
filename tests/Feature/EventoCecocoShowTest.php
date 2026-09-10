<?php

namespace Tests\Feature;

use App\Models\DetalleExpedienteCecoco;
use App\Models\EventoCecoco;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventoCecocoShowTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Timeline con una fase de respuesta calculable ("En desplazamiento" →
     * "En atención") y una acción cuya descripción CECOCO entrega en inglés,
     * para probar tanto el tiempo de respuesta como la traducción.
     */
    private function timelineDeEjemplo(): array
    {
        return [
            ['fecha_hora' => '03/01/2026 11:49:11', 'operador' => 'OP1', 'descripcion' => 'Event creation', 'estado' => ''],
            ['fecha_hora' => '03/01/2026 11:49:13', 'operador' => 'OP1', 'descripcion' => 'Resource assignation', 'estado' => 'P1020, En desplazamiento'],
            ['fecha_hora' => '03/01/2026 12:11:10', 'operador' => 'OP1', 'descripcion' => 'Resource change of status', 'estado' => 'P1020, En atención'],
        ];
    }

    private function crearEventoConDetalle(array $extraDetalle = []): EventoCecoco
    {
        $evento = EventoCecoco::factory()->create([
            'fecha_hora' => '2026-01-03 11:49:11',
            'tipo_servicio' => 'ROBO',
        ]);

        DetalleExpedienteCecoco::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_consulta' => now(),
            'detalle_json' => array_merge([
                'nro_expediente' => $evento->nro_expediente,
                'fecha_hora_inicial' => '03/01/2026 11:49:11',
                'operador_inicial' => 'OP1',
                'tipo_servicio' => $evento->tipo_servicio,
                'direccion' => $evento->direccion,
                'telefono' => null,
                'descripcion_inicial' => $evento->descripcion,
                'historial' => [],
                'timeline' => $this->timelineDeEjemplo(),
                'total_eventos' => 3,
                'tramites' => [
                    ['unidad' => 'P1020', 'h_asig' => '11:49', 'h_llegada' => '12:00', 'h_f_atencion' => '12:30'],
                ],
                'total_tramites' => 1,
                'cierre' => [],
            ], $extraDetalle),
        ]);

        return $evento->fresh('detalle');
    }

    private function usuarioConPermiso(string $permiso): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(Permission::findOrCreate($permiso, 'web'));

        return $usuario;
    }

    public function test_el_resumen_del_evento_muestra_el_tiempo_de_respuesta(): void
    {
        $usuario = User::factory()->create();
        $evento = $this->crearEventoConDetalle();

        $this->actingAs($usuario)->get(route('cecoco.show', $evento))
            ->assertOk()
            ->assertSee('Tiempo de respuesta')
            ->assertSee('22 min', false);
    }

    public function test_el_expediente_traduce_la_cronologia_y_permite_ocultarla(): void
    {
        $usuario = $this->usuarioConPermiso('ver-expediente-cecoco');
        $evento = $this->crearEventoConDetalle();

        $response = $this->actingAs($usuario)->get(route('cecoco.expediente', $evento));

        $response->assertOk()
            ->assertSee('Creación del evento')
            ->assertSee('Asignación de recurso')
            ->assertDontSee('Event creation')
            ->assertDontSee('Resource assignation')
            ->assertSee('Ocultar / mostrar')
            ->assertSee('Tiempo de respuesta');
    }

    public function test_el_pdf_interno_incluye_el_tiempo_de_respuesta_cuando_esta_disponible(): void
    {
        $usuario = $this->usuarioConPermiso('ver-expediente-cecoco');
        $evento = $this->crearEventoConDetalle();

        $this->actingAs($usuario)->get(route('cecoco.exportar.pdf-interno', $evento))
            ->assertOk()
            ->assertSee('Tiempo de respuesta')
            ->assertSee('22 min', false)
            ->assertSee('Creación del evento');
    }

    public function test_el_pdf_interno_no_muestra_tiempo_de_respuesta_si_no_se_puede_calcular(): void
    {
        $usuario = $this->usuarioConPermiso('ver-expediente-cecoco');
        $evento = $this->crearEventoConDetalle([
            'timeline' => [
                ['fecha_hora' => '03/01/2026 11:49:11', 'operador' => 'OP1', 'descripcion' => 'Event creation', 'estado' => ''],
            ],
        ]);

        $this->actingAs($usuario)->get(route('cecoco.exportar.pdf-interno', $evento))
            ->assertOk()
            ->assertDontSee('Tiempo de respuesta');
    }

    public function test_la_vista_movil_muestra_recursos_cronologia_traducida_y_tiempo_de_respuesta(): void
    {
        $usuario = $this->usuarioConPermiso('ver-expediente-cecoco');
        $evento = $this->crearEventoConDetalle();

        $response = $this->actingAs($usuario)->get(route('movil.eventos.show', $evento));

        $response->assertOk()
            ->assertViewIs('movil.eventos.show')
            ->assertSee('Recursos asignados')
            ->assertSee('Creación del evento')
            ->assertDontSee('Event creation')
            ->assertSee('Tiempo de respuesta')
            ->assertSee('22 min', false)
            ->assertSee('Imprimir Parte de Novedad')
            ->assertSee('PDF Interno Completo');
    }
}
