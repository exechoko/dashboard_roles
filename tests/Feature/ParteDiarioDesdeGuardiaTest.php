<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\Recurso;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParteDiarioDesdeGuardiaTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string $permiso = 'generar-parte-diario'): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permiso);

        return $user;
    }

    private function generarParte(Recurso $recurso, array $dotacion, int $chofer, string $guardia): void
    {
        $this->post(route('flota-911.informes.parte-diario.generar'), [
            'fecha'        => '2099-03-10',
            'guardia'      => $guardia,
            'horario'      => '06_18',
            'fecha_inicio' => '2099-03-10T06:15',
            'fecha_fin'    => '2099-03-10T18:15',
            'recursos'     => [[
                'id'         => $recurso->id,
                'estado_dia' => 'circula',
                'zona'       => 3,
                'ht'         => 'ZZ9-TEST',
                'dotacion'   => $dotacion,
                'chofer_id'  => $chofer,
            ]],
            'novedades' => ['sala_armas' => 'Rubro de prueba desde guardia'],
        ])->assertRedirect();
    }

    public function test_trae_los_datos_del_ultimo_parte_de_la_guardia(): void
    {
        $this->actingAs($this->usuario());

        $recurso = Recurso::whereNotNull('vehiculo_id')->firstOrFail();
        $personal = Personal::query()->take(2)->pluck('id')->all();
        $this->assertCount(2, $personal, 'Se necesitan al menos 2 personas en la BD de prueba.');

        $this->generarParte($recurso, $personal, $personal[0], 'guardia_2');

        $data = $this->getJson(route('flota-911.informes.parte-diario.desde-guardia', ['guardia' => 'guardia_2']))
            ->assertOk()
            ->json();

        $this->assertTrue($data['encontrado']);

        $seccion = $data['secciones'][$recurso->destino_id];
        $r = $seccion['recursos'][$recurso->id];

        $this->assertSame('circula', $r['estado_dia']);
        $this->assertSame('3', $r['zona']);
        $this->assertSame('ZZ9-TEST', $r['ht']);
        $this->assertEqualsCanonicalizing($personal, $r['dotacion']);
        $this->assertSame($personal[0], $r['chofer_id']);
        $this->assertSame('Rubro de prueba desde guardia', $data['novedades']['sala_armas']);
    }

    public function test_no_mezcla_datos_de_otras_guardias(): void
    {
        $this->actingAs($this->usuario());

        $recurso = Recurso::whereNotNull('vehiculo_id')->firstOrFail();
        $personal = Personal::query()->take(2)->pluck('id')->all();

        $this->generarParte($recurso, $personal, $personal[0], 'guardia_2');

        $contenido = $this->getJson(route('flota-911.informes.parte-diario.desde-guardia', ['guardia' => 'guardia_4']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('ZZ9-TEST', $contenido);
    }

    public function test_sin_permiso_no_puede_consultar(): void
    {
        $this->actingAs($this->usuario('ver-flota-911'))
            ->getJson(route('flota-911.informes.parte-diario.desde-guardia', ['guardia' => 'guardia_1']))
            ->assertForbidden();
    }
}
