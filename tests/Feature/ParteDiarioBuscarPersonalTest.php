<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\User;
use App\Services\ParteDiarioBorradorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParteDiarioBuscarPersonalTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string $permiso = 'generar-parte-diario'): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permiso);

        return $user;
    }

    private function coincideConSeccion(Personal $personal, array $patrones): bool
    {
        foreach ($patrones as $patron) {
            $segmentos = array_map(fn (string $s): string => preg_quote($s, '/'), explode('%', $patron));
            $regex = '/^' . implode('.*', $segmentos) . '$/ui';

            if (preg_match($regex, (string) $personal->funcion_personal911) === 1) {
                return true;
            }
        }

        return false;
    }

    public function test_sin_query_devuelve_solo_personal_de_la_seccion_del_tipo(): void
    {
        $this->actingAs($this->usuario());

        $respuesta = $this->getJson(route('flota-911.informes.parte-diario.personal.buscar', ['tipo' => 'motos']))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($respuesta['results'], 'Se necesita personal real de motorizada en la BD para este test.');
        $this->assertLessThanOrEqual(30, count($respuesta['results']));

        $patrones = ParteDiarioBorradorService::patronesFuncionSeccion('motos');

        foreach ($respuesta['results'] as $r) {
            $this->assertArrayHasKey('id', $r);
            $this->assertArrayHasKey('text', $r);

            $personal = Personal::findOrFail($r['id']);
            $this->assertTrue(
                $this->coincideConSeccion($personal, $patrones),
                "El id {$r['id']} ({$personal->funcion_personal911}) no matchea ningún patrón de motos."
            );
        }
    }

    public function test_con_query_busca_en_todo_el_personal_911(): void
    {
        $this->actingAs($this->usuario());

        $respuesta = $this->getJson(route('flota-911.informes.parte-diario.personal.buscar', ['tipo' => 'moviles', 'q' => 'a']))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($respuesta['results']);
        $this->assertLessThanOrEqual(30, count($respuesta['results']));
    }

    public function test_sin_permiso_no_puede_buscar(): void
    {
        $this->actingAs($this->usuario('ver-flota-911'))
            ->getJson(route('flota-911.informes.parte-diario.personal.buscar', ['tipo' => 'moviles']))
            ->assertForbidden();
    }

    public function test_tipo_invalido_es_rechazado(): void
    {
        $this->actingAs($this->usuario())
            ->getJson(route('flota-911.informes.parte-diario.personal.buscar', ['tipo' => 'bicicletas']))
            ->assertStatus(422);
    }
}
