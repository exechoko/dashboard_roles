<?php

namespace Tests\Feature;

use App\Models\ParteDiario;
use App\Models\ParteDiarioAsignacion;
use App\Models\ParteDiarioNovedades;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParteDiarioCapturaTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('generar-parte-diario');

        return $user;
    }

    private function recursoDeMotos(): Recurso
    {
        return Recurso::query()
            ->whereNotNull('vehiculo_id')
            ->whereHas('destino', fn ($q) => $q->where('nombre', 'like', '%Motorizada%'))
            ->firstOrFail();
    }

    private function recursoDeMoviles(): Recurso
    {
        return Recurso::query()
            ->whereNotNull('vehiculo_id')
            ->whereHas('destino', fn ($q) => $q->where('nombre', 'like', '%Patrulla%')
                ->where('nombre', 'not like', '%Motorizada%'))
            ->firstOrFail();
    }

    /**
     * @param  array<int, array<string, mixed>>  $recursos
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $recursos, array $overrides = []): array
    {
        return array_merge([
            'tipo'         => 'moviles',
            'fecha'        => '2099-05-20',
            'guardia'      => 'guardia_3',
            'horario'      => '06_18',
            'fecha_inicio' => '2099-05-20T06:15',
            'fecha_fin'    => '2099-05-20T18:15',
            'recursos'     => $recursos,
        ], $overrides);
    }

    public function test_guardar_crea_una_cabecera_de_parte_por_tipo_a_nivel_division(): void
    {
        $this->actingAs($this->usuario());
        $movil = $this->recursoDeMoviles();
        $moto = $this->recursoDeMotos();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payload([
            ['id' => $movil->id, 'estado_dia' => 'circula'],
        ]))->assertRedirect();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payload(
            [['id' => $moto->id, 'estado_dia' => 'circula']],
            ['tipo' => 'motos'],
        ))->assertRedirect();

        $partes = ParteDiario::where('destino_id', 42)
            ->where('fecha_inicio', '2099-05-20 06:15:00')
            ->pluck('tipo', 'tipo');

        $this->assertEqualsCanonicalizing(
            [ParteDiario::TIPO_MOVILES, ParteDiario::TIPO_MOTOS],
            $partes->values()->all()
        );
    }

    public function test_guarda_zona_ht_y_marca_al_chofer_en_la_dotacion(): void
    {
        $this->actingAs($this->usuario());
        $recurso = $this->recursoDeMoviles();
        [$p1, $p2] = Personal::query()->take(2)->get()->all();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payload([
            [
                'id'         => $recurso->id,
                'estado_dia' => 'circula',
                'zona'       => 2,
                'ht'        => 'HT 26',
                'dotacion'   => [$p1->id, $p2->id],
                'chofer_id'  => $p2->id,
            ],
        ]))->assertRedirect();

        $estado = RecursoEstadoDiario::where('recurso_id', $recurso->id)
            ->where('fecha_inicio', '2099-05-20 06:15:00')->firstOrFail();
        $this->assertSame(2, $estado->zona);
        $this->assertSame('HT 26', $estado->ht);
        $this->assertNotNull($estado->parte_diario_id);

        $dotacion = RecursoDotacion::where('recurso_id', $recurso->id)
            ->where('fecha_inicio', '2099-05-20 06:15:00')->orderBy('orden')->get();
        $this->assertCount(2, $dotacion);
        $this->assertFalse($dotacion[0]->es_chofer);
        $this->assertTrue($dotacion[1]->es_chofer);
        $this->assertSame([0, 1], $dotacion->pluck('orden')->all());
    }

    public function test_guarda_y_reemplaza_las_asignaciones_de_servicios(): void
    {
        $this->actingAs($this->usuario());
        $moto = $this->recursoDeMotos();

        $base = fn (string $texto) => $this->payload(
            [['id' => $moto->id, 'estado_dia' => 'circula']],
            ['tipo' => 'motos', 'asignaciones' => [
                ['grupo' => 'Microcentro', 'nombre' => 'Sector 1', 'asignacion_texto' => $texto],
                ['grupo' => '', 'nombre' => '', 'asignacion_texto' => ''],
            ]],
        );

        $this->post(route('flota-911.informes.parte-diario.generar'), $base('14'))->assertRedirect();
        $this->post(route('flota-911.informes.parte-diario.generar'), $base('31 ht 05'))->assertRedirect();

        $parte = ParteDiario::where('destino_id', 42)->where('tipo', 'motos')
            ->where('fecha_inicio', '2099-05-20 06:15:00')->firstOrFail();
        $asignaciones = ParteDiarioAsignacion::where('parte_diario_id', $parte->id)->get();

        $this->assertCount(1, $asignaciones, 'La fila vacía no debe guardarse y no debe duplicar.');
        $this->assertSame('31 ht 05', $asignaciones->first()->asignacion_texto);
    }

    public function test_guarda_las_novedades_una_sola_vez_por_guardia(): void
    {
        $this->actingAs($this->usuario());
        $recurso = $this->recursoDeMoviles();

        $payload = fn (string $armas) => $this->payload(
            [['id' => $recurso->id, 'estado_dia' => 'circula']],
            ['novedades' => ['sala_armas' => $armas, 'movil_traslado' => '']],
        );

        $this->post(route('flota-911.informes.parte-diario.generar'), $payload('SGTO. PEREZ'))->assertRedirect();
        $this->post(route('flota-911.informes.parte-diario.generar'), $payload('SGTO. GOMEZ'))->assertRedirect();

        $novedades = ParteDiarioNovedades::where(['fecha' => '2099-05-20', 'guardia' => 'guardia_3'])->get();
        $this->assertCount(1, $novedades);
        $this->assertSame('SGTO. GOMEZ', $novedades->first()->contenido['sala_armas']);
        $this->assertArrayNotHasKey('movil_traslado', $novedades->first()->contenido);
    }

    public function test_sin_novedades_no_se_crea_registro(): void
    {
        $this->actingAs($this->usuario());
        $recurso = $this->recursoDeMoviles();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payload([
            ['id' => $recurso->id, 'estado_dia' => 'circula'],
        ]))->assertRedirect();

        $this->assertFalse(
            ParteDiarioNovedades::where(['fecha' => '2099-05-20', 'guardia' => 'guardia_3'])->exists()
        );
    }

    public function test_los_rubros_de_personal_tienen_buscador_y_los_de_moviles_no(): void
    {
        $respuesta = $this->actingAs($this->usuario())
            ->get(route('flota-911.informes.parte-diario', ['tipo' => 'moviles', 'guardia' => 'guardia_3']));

        $respuesta->assertOk()
            ->assertSee('select2-novedad-personal', false)
            ->assertSee('data-rubro="sala_monitoreo"', false)
            ->assertSee('data-rubro="autorizados"', false)
            ->assertDontSee('data-rubro="moviles_qap"', false);
    }

    public function test_novedad_de_personal_admite_varios_funcionarios_escritos_a_mano(): void
    {
        $this->actingAs($this->usuario());
        $recurso = $this->recursoDeMoviles();

        $texto = 'SGTO. PEREZ JUAN; CABO GOMEZ ANA; AGTE. LOPEZ (autorizado verbal)';

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payload(
            [['id' => $recurso->id, 'estado_dia' => 'circula']],
            ['novedades' => ['autorizados' => $texto]],
        ))->assertRedirect();

        $novedades = ParteDiarioNovedades::where(['fecha' => '2099-05-20', 'guardia' => 'guardia_3'])->firstOrFail();
        $this->assertSame($texto, $novedades->contenido['autorizados']);
    }

    public function test_pre_armar_devuelve_novedades_de_sala_y_guardia_interna(): void
    {
        $respuesta = $this->actingAs($this->usuario())
            ->postJson(route('flota-911.informes.parte-diario.pre-armar'), [
                'tipo'         => 'moviles',
                'guardia'      => 'guardia_3',
                'fecha_inicio' => '2099-05-20T06:15',
            ]);

        $respuesta->assertOk()
            ->assertJsonStructure(['novedades', 'guardia_interna', 'licencia_ordinaria']);

        $this->assertNotEmpty($respuesta->json('novedades.sala_armas'));
    }
}
