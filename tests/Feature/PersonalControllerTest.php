<?php

namespace Tests\Feature;

use App\Http\Controllers\PersonalController;
use App\Models\Personal;
use App\Models\PersonalSeccion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PersonalControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function crearFuncionarioEnSeccion(string $seccion, bool $activo = true, bool $enLicencia = false, string $jerarquia = 'Sargento', ?string $apellido = null): Personal
    {
        do {
            $lp = (string) random_int(10000, 99999);
        } while (Personal::withTrashed()->where('lp', $lp)->exists());

        $personal = Personal::create([
            'personal911_id' => random_int(900000, 999999),
            'nombre' => 'Funcionario',
            'apellido' => $apellido ?? 'De Prueba '.uniqid(),
            'lp' => $lp,
            'jerarquia' => $jerarquia,
            'funcion_personal911' => 'Sección Técnica T1',
        ]);

        PersonalSeccion::create([
            'personal_id' => $personal->id,
            'id_lugar_personal911' => 10,
            'seccion' => $seccion,
            'activo' => $activo,
            'en_licencia' => $enLicencia,
            'funcion_actual' => 'Sección Técnica T1',
            'fecha_alta' => now()->subMonth()->toDateString(),
            'fecha_baja' => $activo ? null : now()->toDateString(),
            'motivo_baja' => $activo ? null : PersonalSeccion::MOTIVO_CAMBIO_SECCION,
        ]);

        return $personal;
    }

    public function test_index_lista_solo_los_activos_de_la_seccion_tecnica(): void
    {
        $this->actingAs(User::factory()->create());

        $sufijo = uniqid();
        $jefe = $this->crearFuncionarioEnSeccion(PersonalController::SECCION_TECNICA, jerarquia: 'Subcomisario', apellido: "Jefe{$sufijo}");
        $agente = $this->crearFuncionarioEnSeccion(PersonalController::SECCION_TECNICA, jerarquia: 'Agente', apellido: "Ultimo{$sufijo}");
        $baja = $this->crearFuncionarioEnSeccion(PersonalController::SECCION_TECNICA, activo: false, apellido: "Baja{$sufijo}");
        $otraSeccion = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', apellido: "OtraSeccion{$sufijo}");

        $response = $this->getJson('/tareas/personal-efectivo');

        $response->assertOk();
        $apellidos = collect($response->json())->pluck('apellido');

        $this->assertTrue($apellidos->search($jefe->apellido) < $apellidos->search($agente->apellido));
        $this->assertFalse($apellidos->contains($baja->apellido));
        $this->assertFalse($apellidos->contains($otraSeccion->apellido));
    }

    public function test_index_marca_a_los_funcionarios_en_licencia(): void
    {
        $this->actingAs(User::factory()->create());

        $enLicencia = $this->crearFuncionarioEnSeccion(PersonalController::SECCION_TECNICA, enLicencia: true, apellido: 'DeLicencia');

        $response = $this->getJson('/tareas/personal-efectivo');

        $response->assertOk();
        $response->assertJsonFragment(['apellido' => $enLicencia->apellido, 'en_licencia' => true]);
    }

    public function test_index_html_no_expone_botones_de_alta_baja_o_modificacion(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/tareas/personal-efectivo');

        $response->assertOk();
        $response->assertDontSee('Agregar Funcionario');
        $response->assertDontSee('modalPersonal', false);
    }
}
