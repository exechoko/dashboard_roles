<?php

namespace Tests\Feature;

use App\Models\DetalleExpedienteCecoco;
use App\Models\EventoCecoco;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventoCecocoFiltroSinDetalleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_filtro_sin_detalle_solo_muestra_eventos_sin_expediente_traido(): void
    {
        $usuario = User::factory()->create();

        $operadorUnico = 'TEST_OPERADOR_SIN_DETALLE_' . uniqid();

        $conDetalle = EventoCecoco::factory()->create([
            'nro_expediente' => '9111111',
            'operador' => $operadorUnico,
        ]);
        DetalleExpedienteCecoco::create([
            'evento_cecoco_id' => $conDetalle->id,
            'nro_expediente' => $conDetalle->nro_expediente,
            'fecha_consulta' => now(),
            'detalle_json' => ['nro_expediente' => $conDetalle->nro_expediente],
        ]);

        $sinDetalle = EventoCecoco::factory()->create([
            'nro_expediente' => '9222222',
            'operador' => $operadorUnico,
        ]);

        $response = $this->actingAs($usuario)->get(route('cecoco.index', [
            'operador' => $operadorUnico,
            'sin_detalle' => 1,
        ]));

        $response->assertOk()
            ->assertSee($sinDetalle->nro_expediente)
            ->assertDontSee($conDetalle->nro_expediente);
    }
}
