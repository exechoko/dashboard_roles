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
            'horario'      => '07_19',
            'fecha_inicio' => '2099-01-15T07:00',
            'fecha_fin'    => '2099-01-15T19:00',
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
        ]))->assertOk();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'guardia'      => 'guardia_2',
            'horario'      => '19_07',
            'fecha_inicio' => '2099-01-15T19:00',
            'fecha_fin'    => '2099-01-16T07:00',
            'recursos'     => [['id' => $recurso->id, 'estado_dia' => 'circula']],
        ]))->assertOk();

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
        ]))->assertOk();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [['id' => $recurso->id, 'estado_dia' => 'fuera_de_servicio', 'motivo' => 'Choque']],
        ]))->assertOk();

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
        ]))->assertOk();

        $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'guardia'      => 'guardia_2',
            'horario'      => '19_07',
            'fecha_inicio' => '2099-01-15T19:00',
            'fecha_fin'    => '2099-01-16T07:00',
            'recursos'     => [[
                'id' => $recursos[1]->id, 'estado_dia' => 'circula',
                'dotacion' => [$personal->id],
            ]],
        ]))->assertOk();

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
            ->where('fecha_inicio', '2099-01-15 07:00:00')
            ->count());
    }

    public function test_la_vista_del_parte_muestra_los_selectores_de_guardia_y_turno(): void
    {
        $response = $this->actingAs($this->usuarioConPermiso())
            ->get(route('flota-911.informes.parte-diario', [
                'fecha' => '2099-01-15', 'guardia' => 'guardia_1', 'horario' => '07_19',
            ]));

        $response->assertOk();
        $response->assertSee('Inicio del turno');
        $response->assertSee('Cargar parte guardado de este turno');
    }

    public function test_los_titulos_del_docx_se_mayusculizan_conservando_los_acentos(): void
    {
        $this->actingAs($this->usuarioConPermiso());
        $recurso = Recurso::whereNotNull('vehiculo_id')->firstOrFail();

        $response = $this->post(route('flota-911.informes.parte-diario.generar'), $this->payloadTurno([
            'recursos' => [['id' => $recurso->id, 'estado_dia' => 'circula']],
        ]));
        $response->assertOk();

        $xml = $this->textoDelDocx($response);

        $this->assertStringContainsString('DIVISIÓN 911 Y VIDEOVIGILANCIA', $xml);
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
