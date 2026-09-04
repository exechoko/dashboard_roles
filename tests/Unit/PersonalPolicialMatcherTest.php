<?php

namespace Tests\Unit;

use App\Services\PersonalPolicialMatcher;
use Tests\TestCase;

class PersonalPolicialMatcherTest extends TestCase
{
    public function test_confirma_cuando_hay_un_solo_candidato(): void
    {
        $candidato = ['id' => 1, 'nombre_completo' => 'Sargento Perez, Juan', 'jerarquia' => 'Sargento', 'lp' => '1234'];

        $resultado = (new PersonalPolicialMatcher())->cruzar(
            [['jerarquia' => 'Sgto.', 'apellido' => 'Perez', 'nombre' => '', 'movil' => '901']],
            fn () => [$candidato]
        );

        $this->assertSame('confirmado', $resultado[0]['estado']);
        $this->assertCount(1, $resultado[0]['candidatos']);
        $this->assertSame('901', $resultado[0]['movil']);
    }

    public function test_queda_ambiguo_cuando_hay_varios_candidatos(): void
    {
        $resultado = (new PersonalPolicialMatcher())->cruzar(
            [['jerarquia' => 'Cabo', 'apellido' => 'Gomez', 'nombre' => '', 'movil' => '']],
            fn () => [
                ['id' => 1, 'nombre_completo' => 'Cabo Gomez, Juan', 'jerarquia' => 'Cabo', 'lp' => '1'],
                ['id' => 2, 'nombre_completo' => 'Cabo Gomez, Pedro', 'jerarquia' => 'Cabo', 'lp' => '2'],
            ]
        );

        $this->assertSame('ambiguo', $resultado[0]['estado']);
        $this->assertCount(2, $resultado[0]['candidatos']);
    }

    public function test_sin_coincidencia_cuando_no_hay_candidatos(): void
    {
        $resultado = (new PersonalPolicialMatcher())->cruzar(
            [['jerarquia' => 'Oficial', 'apellido' => 'Inexistente', 'nombre' => '', 'movil' => '']],
            fn () => []
        );

        $this->assertSame('sin_coincidencia', $resultado[0]['estado']);
        $this->assertSame([], $resultado[0]['candidatos']);
    }

    public function test_sin_apellido_no_busca_y_queda_sin_coincidencia(): void
    {
        $llamado = false;

        $resultado = (new PersonalPolicialMatcher())->cruzar(
            [['jerarquia' => 'Sargento', 'apellido' => '', 'nombre' => '', 'movil' => '']],
            function () use (&$llamado) {
                $llamado = true;
                return [];
            }
        );

        $this->assertFalse($llamado);
        $this->assertSame('sin_coincidencia', $resultado[0]['estado']);
    }

    public function test_conserva_el_orden_y_los_datos_de_cada_mencion(): void
    {
        $resultado = (new PersonalPolicialMatcher())->cruzar(
            [
                ['jerarquia' => 'Sgto.', 'apellido' => 'Perez', 'nombre' => 'Juan', 'movil' => '901'],
                ['jerarquia' => 'Cabo', 'apellido' => 'Gomez', 'nombre' => '', 'movil' => '902'],
            ],
            fn (string $apellido) => $apellido === 'Perez' ? [['id' => 1, 'nombre_completo' => 'x', 'jerarquia' => 'y', 'lp' => 'z']] : []
        );

        $this->assertSame('Perez', $resultado[0]['apellido']);
        $this->assertSame('confirmado', $resultado[0]['estado']);
        $this->assertSame('Gomez', $resultado[1]['apellido']);
        $this->assertSame('sin_coincidencia', $resultado[1]['estado']);
    }
}
