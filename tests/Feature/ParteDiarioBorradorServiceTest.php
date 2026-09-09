<?php

namespace Tests\Feature;

use App\Models\ParteDiario;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoEstadoDiario;
use App\Services\ParteDiarioBorradorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ParteDiarioBorradorServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ParteDiarioBorradorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ParteDiarioBorradorService();
    }

    public function test_el_borrador_de_motos_trae_jefes_y_motoristas_de_la_guardia(): void
    {
        $borrador = $this->service->armar(
            ParteDiario::TIPO_MOTOS,
            'guardia_3',
            Carbon::parse('2099-01-15 06:20'),
        );

        $this->assertSame('Guardia 3', $borrador['guardia_label']);
        $this->assertSame('1', $borrador['turno']);

        $this->assertSame('Jefe Sección Motorizada', $borrador['mando']['jefe']?->funcion_personal911);
        $this->assertSame('2° Jefe Sección Motorizada', $borrador['mando']['segundo_jefe']?->funcion_personal911);

        $this->assertGreaterThan(0, $borrador['personal_calle']->count());
        $this->assertTrue(
            $borrador['personal_calle']->every(fn (Personal $p) => $p->funcion_personal911 === 'Motoristas G3')
        );
    }

    public function test_la_nomina_de_calle_queda_ordenada_por_jerarquia(): void
    {
        $calle = $this->service->armar(
            ParteDiario::TIPO_MOTOS,
            'guardia_3',
            Carbon::parse('2099-01-15 06:20'),
        )['personal_calle'];

        $pesos = $calle->map(fn (Personal $p) => Personal::pesoJerarquia($p->jerarquia))->all();
        $ordenados = $pesos;
        sort($ordenados);

        $this->assertSame($ordenados, $pesos, 'La nómina no está ordenada de mayor a menor jerarquía.');
    }

    public function test_el_borrador_de_moviles_usa_moviles_de_la_guardia(): void
    {
        $borrador = $this->service->armar(
            ParteDiario::TIPO_MOVILES,
            'guardia_3',
            Carbon::parse('2099-01-15 06:15'),
        );

        $this->assertGreaterThan(0, $borrador['personal_calle']->count());
        $this->assertTrue(
            $borrador['personal_calle']->every(fn (Personal $p) => $p->funcion_personal911 === 'Móviles G3')
        );
        $this->assertSame('Jefe Patrulla 911', $borrador['mando']['jefe_patrulla']?->funcion_personal911);
        // "Oficial de Calle" puede no existir en la base: el borrador no debe romperse.
        $this->assertArrayHasKey('jefe_calle', $borrador['mando']);
    }

    public function test_las_novedades_se_prellenan_con_salas_de_la_guardia(): void
    {
        $novedades = $this->service->armar(
            ParteDiario::TIPO_MOVILES,
            'guardia_3',
            Carbon::parse('2099-01-15 06:15'),
        )['novedades'];

        $this->assertArrayHasKey('sala_armas', $novedades);
        $this->assertArrayHasKey('sala_monitoreo', $novedades);
        $this->assertArrayHasKey('personal_guardia', $novedades);
        $this->assertNotSame('', $novedades['sala_armas']);
    }

    public function test_novedades_desde_estados_agrupa_por_estado_del_dia(): void
    {
        $recursos = Recurso::query()->whereNotNull('vehiculo_id')->take(3)->get();
        $fechaInicio = Carbon::parse('2099-02-01 07:00');

        $estados = new Collection();
        foreach (['fuera_de_servicio', 'a_presto', 'de_traslado'] as $i => $estadoDia) {
            $estados->push(new RecursoEstadoDiario([
                'recurso_id'   => $recursos[$i]->id,
                'estado_dia'   => $estadoDia,
                'motivo'       => $estadoDia === 'fuera_de_servicio' ? 'chapa y pintura' : null,
                'fecha_inicio' => $fechaInicio,
            ]));
        }
        $estados->each(fn (RecursoEstadoDiario $e) => $e->setRelation('recurso', $recursos->firstWhere('id', $e->recurso_id)));

        $resultado = $this->service->novedadesDesdeEstados($estados);

        $this->assertStringContainsString($recursos[0]->nombre, $resultado['moviles_fuera_servicio']);
        $this->assertStringContainsString('chapa y pintura', $resultado['moviles_fuera_servicio']);
        $this->assertStringContainsString($recursos[1]->nombre, $resultado['movil_presto']);
        $this->assertStringContainsString($recursos[2]->nombre, $resultado['movil_traslado']);
        $this->assertSame('', $resultado['movil_comision']);
    }

    public function test_el_turno_nocturno_se_detecta_por_la_hora_de_inicio(): void
    {
        $borrador = $this->service->armar(
            ParteDiario::TIPO_MOTOS,
            'guardia_3',
            Carbon::parse('2099-01-15 18:20'),
        );

        $this->assertSame('2', $borrador['turno']);
    }
}
