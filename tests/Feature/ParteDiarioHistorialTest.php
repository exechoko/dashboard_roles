<?php

namespace Tests\Feature;

use App\Models\ParteDiario;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParteDiarioHistorialTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string $permiso): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permiso);

        return $user;
    }

    private function generarParte(string $guardia, string $fecha, array $over = []): ParteDiario
    {
        $recurso = Recurso::whereNotNull('vehiculo_id')->firstOrFail();
        $personal = Personal::query()->take(2)->pluck('id')->all();

        $this->actingAs($this->usuario('generar-parte-diario'))
            ->post(route('flota-911.informes.parte-diario.generar'), array_merge([
                'tipo'         => 'moviles',
                'fecha'        => $fecha,
                'guardia'      => $guardia,
                'horario'      => '06_18',
                'fecha_inicio' => $fecha . 'T06:15',
                'fecha_fin'    => $fecha . 'T18:15',
                'recursos'     => [[
                    'id'         => $recurso->id,
                    'estado_dia' => 'circula',
                    'zona'       => 2,
                    'dotacion'   => $personal,
                    'chofer_id'  => $personal[0],
                ]],
                'novedades' => ['sala_armas' => 'Novedad histórica de prueba'],
            ], $over))
            ->assertRedirect();

        return ParteDiario::where('guardia', $guardia)
            ->where('fecha_inicio', $fecha . ' 06:15:00')
            ->firstOrFail();
    }

    public function test_sin_permiso_no_accede_al_historial(): void
    {
        $parte = $this->generarParte('guardia_1', '2099-04-01');

        $lector = $this->usuario('ver-flota-911');
        $this->actingAs($lector)->get(route('flota-911.partes-diarios.index'))->assertForbidden();
        $this->actingAs($lector)->get(route('flota-911.partes-diarios.show', $parte->id))->assertForbidden();
    }

    public function test_el_indice_lista_y_filtra_por_guardia(): void
    {
        $p2 = $this->generarParte('guardia_2', '2099-04-02');
        $this->generarParte('guardia_3', '2099-04-03');

        $lector = $this->usuario('ver-historial-parte-diario');

        $this->actingAs($lector)
            ->get(route('flota-911.partes-diarios.index', ['guardia' => 'guardia_2']))
            ->assertOk()
            ->assertSee('02/04/2099')
            ->assertDontSee('03/04/2099');
    }

    public function test_el_detalle_muestra_tripulacion_y_novedades(): void
    {
        $parte = $this->generarParte('guardia_1', '2099-04-04');
        $nombrePersonal = Personal::find(Personal::query()->take(1)->value('id'))->nombre_completo;

        $this->actingAs($this->usuario('ver-historial-parte-diario'))
            ->get(route('flota-911.partes-diarios.show', $parte->id))
            ->assertOk()
            ->assertSee('Novedad histórica de prueba')
            ->assertSee(e($nombrePersonal))
            ->assertSee('chofer');
    }

    public function test_descarga_el_docx_historico(): void
    {
        $parte = $this->generarParte('guardia_1', '2099-04-05');

        $this->actingAs($this->usuario('ver-historial-parte-diario'))
            ->get(route('flota-911.partes-diarios.docx', $parte->id))
            ->assertOk()
            ->assertDownload();
    }
}
