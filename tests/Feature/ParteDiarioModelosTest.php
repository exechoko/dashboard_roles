<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\ParteDiario;
use App\Models\ParteDiarioAsignacion;
use App\Models\ParteDiarioConsigna;
use App\Models\ParteDiarioNovedades;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParteDiarioModelosTest extends TestCase
{
    use DatabaseTransactions;

    private const DIVISION_911_ID = 42;

    public function test_novedades_completa_los_14_rubros_con_sin_novedad(): void
    {
        $novedades = new ParteDiarioNovedades([
            'contenido' => ['sala_armas' => 'SGTO. PEREZ JUAN'],
        ]);

        $rubros = $novedades->rubrosCompletos();

        $this->assertCount(14, $rubros);
        $this->assertSame('SGTO. PEREZ JUAN', $rubros['sala_armas']['valor']);
        $this->assertSame('PERSONAL SALA DE ARMAS', $rubros['sala_armas']['etiqueta']);
        $this->assertSame(ParteDiarioNovedades::SIN_NOVEDAD, $rubros['movil_traslado']['valor']);
    }

    public function test_parte_diario_relaciona_estados_dotaciones_y_asignaciones(): void
    {
        $seccion = Destino::where('parent_id', self::DIVISION_911_ID)->firstOrFail();
        $recurso = Recurso::query()->whereNotNull('vehiculo_id')->firstOrFail();
        $personal = Personal::query()->firstOrFail();
        $user = User::factory()->create();
        $fechaInicio = Carbon::parse('2099-04-10 06:15');

        $parte = ParteDiario::create([
            'destino_id'   => $seccion->id,
            'tipo'         => ParteDiario::TIPO_MOVILES,
            'fecha'        => '2099-04-10',
            'guardia'      => 'guardia_2',
            'horario'      => '06_18',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => '2099-04-10 18:15',
            'user_id'      => $user->id,
        ]);

        RecursoEstadoDiario::create([
            'parte_diario_id' => $parte->id,
            'recurso_id'      => $recurso->id,
            'guardia'         => 'guardia_2',
            'horario'         => '06_18',
            'zona'            => 2,
            'ht'             => 'HT 26',
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => '2099-04-10 18:15',
            'estado_dia'      => 'circula',
            'user_id'         => $user->id,
        ]);

        RecursoDotacion::create([
            'parte_diario_id' => $parte->id,
            'recurso_id'      => $recurso->id,
            'personal_id'     => $personal->id,
            'es_chofer'       => true,
            'orden'           => 1,
            'guardia'         => 'guardia_2',
            'horario'         => '06_18',
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => '2099-04-10 18:15',
            'user_id'         => $user->id,
        ]);

        ParteDiarioAsignacion::create([
            'parte_diario_id'  => $parte->id,
            'grupo'            => 'Microcentro',
            'nombre'          => 'Sector 1',
            'asignacion_texto' => '31 ht 05',
            'orden'           => 0,
        ]);

        $parte->refresh()->load('estadosDiarios', 'dotaciones', 'asignaciones', 'seccion');

        $this->assertCount(1, $parte->estadosDiarios);
        $this->assertSame(2, $parte->estadosDiarios->first()->zona);
        $this->assertTrue($parte->dotaciones->first()->es_chofer);
        $this->assertSame('31 ht 05', $parte->asignaciones->first()->asignacion_texto);
        $this->assertSame($seccion->id, $parte->seccion->id);
    }

    public function test_consignas_scope_activas(): void
    {
        ParteDiarioConsigna::factory()->create(['nombre' => 'Consigna viva ' . uniqid()]);
        ParteDiarioConsigna::factory()->inactiva()->create(['nombre' => 'Consigna muerta ' . uniqid()]);

        $activas = ParteDiarioConsigna::activas()->get();

        $this->assertTrue($activas->every(fn (ParteDiarioConsigna $c) => $c->activa === true));
    }

    public function test_peso_jerarquia_ordena_de_mayor_a_menor(): void
    {
        $this->assertLessThan(
            Personal::pesoJerarquia('Agente'),
            Personal::pesoJerarquia('Subof. Mayor'),
        );
        $this->assertLessThan(
            Personal::pesoJerarquia('Sargento'),
            Personal::pesoJerarquia('Sgto. Primero'),
        );
        $this->assertSame(
            count(Personal::JERARQUIAS_ORDEN) + 1,
            Personal::pesoJerarquia('Jerarquía inventada'),
        );
    }
}
