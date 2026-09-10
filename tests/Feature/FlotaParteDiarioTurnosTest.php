<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

class FlotaParteDiarioTurnosTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioConPermiso(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('generar-parte-diario');

        return $user;
    }

    private function payloadTurno(array $overrides = []): array
    {
        return array_merge([
            'fecha'        => '2099-01-15',
            'guardia'      => 'guardia_1',
            'horario'      => '06_18',
            'fecha_inicio' => '2099-01-15T06:15',
            'fecha_fin'    => '2099-01-15T18:15',
        ], $overrides);
    }

    private function textoDelDocx(TestResponse $response): string
    {
        $path = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'El .docx generado no es un ZIP válido.');
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml ?: '';
    }

    public function test_los_dos_turnos_del_mismo_dia_conviven_sin_pisarse(): void
    {
        $this->actingAs($this->usuarioConPermiso());
        $recurso = Recurso::whereNotNull('vehiculo_id')->firstOrFail();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [['id' => $recurso->id, 'estado_dia' => 'reserva', 'motivo' => 'En taller']],
        ]))->assertRedirect();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'guardia'      => 'guardia_2',
            'horario'      => '18_06',
            'fecha_inicio' => '2099-01-15T18:15',
            'fecha_fin'    => '2099-01-16T06:15',
            'recursos'     => [['id' => $recurso->id, 'estado_dia' => 'circula']],
        ]))->assertRedirect();

        $estados = RecursoEstadoDiario::where('recurso_id', $recurso->id)
            ->where('fecha_inicio', '>=', '2099-01-01')
            ->orderBy('fecha_inicio')
            ->get();

        $this->assertCount(2, $estados);
        $this->assertSame(['guardia_1', 'guardia_2'], $estados->pluck('guardia')->all());
        $this->assertSame(['reserva', 'circula'], $estados->pluck('estado_dia')->all());
    }

    public function test_reguardar_el_mismo_turno_actualiza_en_lugar_de_duplicar(): void
    {
        $this->actingAs($this->usuarioConPermiso());
        $recurso = Recurso::whereNotNull('vehiculo_id')->firstOrFail();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [['id' => $recurso->id, 'estado_dia' => 'circula']],
        ]))->assertRedirect();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [['id' => $recurso->id, 'estado_dia' => 'fuera_de_servicio', 'motivo' => 'Choque']],
        ]))->assertRedirect();

        $estados = RecursoEstadoDiario::where('recurso_id', $recurso->id)
            ->where('fecha_inicio', '>=', '2099-01-01')
            ->get();
        $this->assertCount(1, $estados);
        $this->assertSame('fuera_de_servicio', $estados->first()->estado_dia);
    }

    public function test_un_funcionario_puede_figurar_en_dos_turnos_distintos_del_mismo_dia(): void
    {
        $this->actingAs($this->usuarioConPermiso());
        $recursos = Recurso::whereNotNull('vehiculo_id')->take(2)->get();
        $personal = Personal::firstOrFail();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [[
                'id' => $recursos[0]->id, 'estado_dia' => 'circula',
                'dotacion' => [$personal->id],
            ]],
        ]))->assertRedirect();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'guardia'      => 'guardia_2',
            'horario'      => '18_06',
            'fecha_inicio' => '2099-01-15T18:15',
            'fecha_fin'    => '2099-01-16T06:15',
            'recursos'     => [[
                'id' => $recursos[1]->id, 'estado_dia' => 'circula',
                'dotacion' => [$personal->id],
            ]],
        ]))->assertRedirect();

        $this->assertSame(2, RecursoDotacion::where('personal_id', $personal->id)
            ->whereIn('recurso_id', $recursos->pluck('id'))
            ->where('fecha_inicio', '>=', '2099-01-01')
            ->count());
    }

    public function test_un_funcionario_en_dos_recursos_del_mismo_parte_es_rechazado(): void
    {
        $this->actingAs($this->usuarioConPermiso());
        $recursos = Recurso::whereNotNull('vehiculo_id')->take(2)->get();
        $personal = Personal::firstOrFail();

        $response = $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [
                ['id' => $recursos[0]->id, 'estado_dia' => 'circula', 'dotacion' => [$personal->id]],
                ['id' => $recursos[1]->id, 'estado_dia' => 'circula', 'dotacion' => [$personal->id]],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('dotacion');
        $this->assertSame(0, RecursoEstadoDiario::whereIn('recurso_id', $recursos->pluck('id'))
            ->where('fecha_inicio', '2099-01-15 06:15:00')
            ->count());
    }

    public function test_la_vista_del_parte_muestra_los_selectores_de_guardia_y_turno(): void
    {
        $response = $this->actingAs($this->usuarioConPermiso())
            ->get(route('flota-911.informes.parte-diario', [
                'fecha' => '2099-01-15', 'guardia' => 'guardia_1', 'horario' => '06_18',
            ]));

        $response->assertOk();
        $response->assertSee('Inicio del turno');
        $response->assertSee('Cargar parte guardado de este turno');
    }

    public function test_el_docx_del_parte_se_descarga_con_acentos_en_mayusculas(): void
    {
        $this->actingAs($this->usuarioConPermiso());
        $recurso = Recurso::query()
            ->whereNotNull('vehiculo_id')
            ->whereHas('destino', fn ($q) => $q->where('nombre', 'like', '%Patrulla%')
                ->where('nombre', 'not like', '%Motorizada%'))
            ->firstOrFail();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [['id' => $recurso->id, 'estado_dia' => 'circula']],
        ]))->assertRedirect();

        $response = $this->get(route('flota-911.informes.parte-diario.docx', [
            'seccion'      => $recurso->destino_id,
            'fecha_inicio' => '2099-01-15T06:15',
        ]));
        $response->assertOk();

        $xml = $this->textoDelDocx($response);

        $this->assertStringContainsString('DIVISIÓN 911 Y VIDEO VIGILANCIA', $xml);
        $this->assertStringNotContainsString('DIVISIóN', $xml);
    }

    public function test_fecha_fin_debe_ser_posterior_al_inicio(): void
    {
        $this->actingAs($this->usuarioConPermiso());

        $response = $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'fecha_fin' => '2099-01-15T06:00',
        ]));

        $response->assertSessionHasErrors('fecha_fin');
    }
}
