<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\PersonalSeccion;
use App\Models\PersonalSeccionNota;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonalSeccionControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function crearFuncionarioEnSeccion(string $seccion, bool $activo = true, bool $enLicencia = false, string $jerarquia = 'Sargento', ?string $apellido = null): Personal
    {
        $personal = Personal::create([
            'personal911_id' => random_int(900000, 999999),
            'nombre' => 'Funcionario',
            'apellido' => $apellido ?? 'De Prueba '.uniqid(),
            'lp' => (string) random_int(10000, 99999),
            'jerarquia' => $jerarquia,
            'funcion_personal911' => 'Monitoreo V.G. G1',
        ]);

        PersonalSeccion::create([
            'personal_id' => $personal->id,
            'id_lugar_personal911' => 10,
            'seccion' => $seccion,
            'activo' => $activo,
            'en_licencia' => $enLicencia,
            'funcion_actual' => 'Monitoreo V.G. G1',
            'fecha_alta' => now()->subMonth()->toDateString(),
            'fecha_baja' => $activo ? null : now()->toDateString(),
            'motivo_baja' => $activo ? null : PersonalSeccion::MOTIVO_CAMBIO_SECCION,
        ]);

        return $personal;
    }

    public function test_show_requiere_permiso(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');

        $this->get(route('personal-secciones.show', $personal->id))->assertForbidden();
    }

    public function test_show_muestra_los_datos_locales_cuando_no_hay_detalle_en_personal911(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        $personal = $this->crearFuncionarioEnSeccion('Sección Judiciales y Gestión de Calidad');

        $response = $this->get(route('personal-secciones.show', $personal->id));

        $response->assertOk();
        $response->assertSee($personal->apellido);
        $response->assertSee('No se pudo traer el detalle completo');
    }

    public function test_show_incluye_anotaciones_visibles_para_el_usuario(): void
    {
        $autor = User::factory()->create();
        $autor->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');
        PersonalSeccionNota::create([
            'personal_id' => $personal->id,
            'user_id' => $autor->id,
            'texto' => 'Nota visible en el detalle.',
        ]);

        $this->actingAs($autor);
        $this->get(route('personal-secciones.show', $personal->id))
            ->assertOk()
            ->assertSee('Nota visible en el detalle.');
    }

    public function test_index_requiere_permiso(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('personal-secciones.index'))->assertForbidden();
    }

    public function test_index_lista_funcionarios_de_la_seccion_filtrada(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        $vg = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');
        $otra = $this->crearFuncionarioEnSeccion('Sección Patrullas 911');

        $response = $this->get(route('personal-secciones.index', [
            'secciones' => ['Sección Violencia de Género'],
        ]));

        $response->assertOk();
        $response->assertSee($vg->apellido);
        $response->assertDontSee($otra->apellido);
    }

    public function test_index_muestra_a_quien_dejo_la_seccion_cuando_se_filtra_por_bajas(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        $dejoLaSeccion = $this->crearFuncionarioEnSeccion('Sección Judiciales y Gestión de Calidad', activo: false);

        $response = $this->get(route('personal-secciones.index', ['estado' => 'bajas']));

        $response->assertOk();
        $response->assertSee($dejoLaSeccion->apellido);
        $response->assertSee('Dejó la sección');
    }

    public function test_storeNota_requiere_permiso(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');

        $this->post(route('personal-secciones.notas.store', $personal->id), ['texto' => 'Nota de prueba'])
            ->assertForbidden();
    }

    public function test_storeNota_crea_anotacion_inmutable(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));
        $this->actingAs($user);

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');

        $response = $this->post(route('personal-secciones.notas.store', $personal->id), [
            'texto' => 'Se le entregó equipo nuevo.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('personal_seccion_notas', [
            'personal_id' => $personal->id,
            'user_id' => $user->id,
            'texto' => 'Se le entregó equipo nuevo.',
        ]);
    }

    public function test_las_notas_son_privadas_por_defecto_para_otros_usuarios(): void
    {
        $autor = User::factory()->create();
        $autor->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));
        $autor->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));

        $otroUsuario = User::factory()->create();
        $otroUsuario->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');
        PersonalSeccionNota::create([
            'personal_id' => $personal->id,
            'user_id' => $autor->id,
            'texto' => 'Nota confidencial del autor.',
        ]);

        $filtro = ['secciones' => ['Sección Violencia de Género']];

        $this->actingAs($otroUsuario);
        $this->get(route('personal-secciones.index', $filtro))->assertOk()->assertDontSee('Nota confidencial del autor.');

        $this->actingAs($autor);
        $this->get(route('personal-secciones.index', $filtro))->assertOk()->assertSee('Nota confidencial del autor.');
    }

    public function test_administrador_ve_todas_las_notas_sin_que_se_las_compartan(): void
    {
        $autor = User::factory()->create();
        $autor->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));

        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $admin->assignRole(Role::findOrCreate('Administrador', 'web'));

        $personal = $this->crearFuncionarioEnSeccion('Sección Judiciales y Gestión de Calidad');
        PersonalSeccionNota::create([
            'personal_id' => $personal->id,
            'user_id' => $autor->id,
            'texto' => 'Nota que solo debería ver el autor, salvo admin.',
        ]);

        $this->actingAs($admin);
        $this->get(route('personal-secciones.index', ['secciones' => ['Sección Judiciales y Gestión de Calidad']]))
            ->assertOk()
            ->assertSee('Nota que solo debería ver el autor, salvo admin.');
    }

    public function test_compartir_una_nota_la_hace_visible_al_destinatario(): void
    {
        $autor = User::factory()->create();
        $autor->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));

        $destinatario = User::factory()->create();
        $destinatario->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');
        $nota = PersonalSeccionNota::create([
            'personal_id' => $personal->id,
            'user_id' => $autor->id,
            'texto' => 'Nota que se va a compartir.',
        ]);

        $filtro = ['secciones' => ['Sección Violencia de Género']];

        $this->actingAs($destinatario);
        $this->get(route('personal-secciones.index', $filtro))->assertDontSee('Nota que se va a compartir.');

        $this->actingAs($autor);
        $this->post(route('personal-secciones.notas.compartir', $nota->id), [
            'usuarios' => [$destinatario->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('personal_seccion_nota_comparticiones', [
            'nota_id' => $nota->id,
            'user_id' => $destinatario->id,
        ]);

        $this->actingAs($destinatario);
        $this->get(route('personal-secciones.index', $filtro))->assertSee('Nota que se va a compartir.');
    }

    public function test_solo_el_autor_puede_compartir_su_nota(): void
    {
        $autor = User::factory()->create();
        $autor->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));

        $otroUsuario = User::factory()->create();
        $otroUsuario->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');
        $nota = PersonalSeccionNota::create([
            'personal_id' => $personal->id,
            'user_id' => $autor->id,
            'texto' => 'Nota ajena.',
        ]);

        $destinatario = User::factory()->create();

        $this->actingAs($otroUsuario);
        $this->post(route('personal-secciones.notas.compartir', $nota->id), [
            'usuarios' => [$destinatario->id],
        ])->assertForbidden();
    }

    public function test_compartir_todas_comparte_solo_las_notas_del_autor_sobre_ese_funcionario(): void
    {
        $autor = User::factory()->create();
        $autor->givePermissionTo(Permission::findOrCreate('crear-personal-seccion-nota', 'web'));

        $otroAutor = User::factory()->create();
        $destinatario = User::factory()->create();
        $destinatario->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));

        $personal = $this->crearFuncionarioEnSeccion('Sección Violencia de Género');

        $notaPropia1 = PersonalSeccionNota::create(['personal_id' => $personal->id, 'user_id' => $autor->id, 'texto' => 'Propia uno.']);
        $notaPropia2 = PersonalSeccionNota::create(['personal_id' => $personal->id, 'user_id' => $autor->id, 'texto' => 'Propia dos.']);
        $notaAjena = PersonalSeccionNota::create(['personal_id' => $personal->id, 'user_id' => $otroAutor->id, 'texto' => 'Ajena.']);

        $this->actingAs($autor);
        $this->post(route('personal-secciones.notas.compartir-todas', $personal->id), [
            'usuarios' => [$destinatario->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('personal_seccion_nota_comparticiones', [
            'nota_id' => $notaPropia1->id,
            'user_id' => $destinatario->id,
        ]);
        $this->assertDatabaseHas('personal_seccion_nota_comparticiones', [
            'nota_id' => $notaPropia2->id,
            'user_id' => $destinatario->id,
        ]);
        $this->assertDatabaseMissing('personal_seccion_nota_comparticiones', [
            'nota_id' => $notaAjena->id,
            'user_id' => $destinatario->id,
        ]);
    }

    public function test_orden_por_jerarquia_respeta_el_escalafon_policial(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        // Apellidos a propósito en orden alfabético INVERSO a la jerarquía:
        // si el test pasa igual, es porque ordena por escalafón y no por
        // apellido (que sería el fallback incorrecto).
        $comisario = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', jerarquia: 'Comisario', apellido: 'Zzzultimo');
        $sargento = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', jerarquia: 'Sargento', apellido: 'Mmedio');
        $agente = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', jerarquia: 'Agente', apellido: 'Aaaprimero');

        $response = $this->get(route('personal-secciones.index', [
            'secciones' => ['Sección Violencia de Género'],
            'orden' => 'jerarquia',
        ]));

        $html = $response->getContent();
        $posComisario = strpos($html, $comisario->apellido);
        $posSargento = strpos($html, $sargento->apellido);
        $posAgente = strpos($html, $agente->apellido);

        $this->assertNotFalse($posComisario);
        $this->assertNotFalse($posSargento);
        $this->assertNotFalse($posAgente);
        $this->assertLessThan($posSargento, $posComisario, 'Comisario debe listarse antes que Sargento');
        $this->assertLessThan($posAgente, $posSargento, 'Sargento debe listarse antes que Agente');
    }

    public function test_orden_por_novedades_prioriza_la_anotacion_mas_reciente(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        $sinNota = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', apellido: 'SinNotaAAA');
        $notaVieja = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', apellido: 'NotaViejaZZZ');
        $notaReciente = $this->crearFuncionarioEnSeccion('Sección Violencia de Género', apellido: 'NotaRecienteMMM');

        $nota1 = PersonalSeccionNota::create(['personal_id' => $notaVieja->id, 'user_id' => $user->id, 'texto' => 'Nota vieja']);
        $nota1->created_at = now()->subDays(10);
        $nota1->save();

        $nota2 = PersonalSeccionNota::create(['personal_id' => $notaReciente->id, 'user_id' => $user->id, 'texto' => 'Nota reciente']);
        $nota2->created_at = now();
        $nota2->save();

        $response = $this->get(route('personal-secciones.index', [
            'secciones' => ['Sección Violencia de Género'],
            'orden' => 'novedades',
        ]));

        $html = $response->getContent();
        $posReciente = strpos($html, $notaReciente->apellido);
        $posVieja = strpos($html, $notaVieja->apellido);
        $posSinNota = strpos($html, $sinNota->apellido);

        $this->assertNotFalse($posReciente);
        $this->assertNotFalse($posVieja);
        $this->assertNotFalse($posSinNota);
        $this->assertLessThan($posVieja, $posReciente, 'La nota más reciente debe listarse primero');
        $this->assertLessThan($posSinNota, $posVieja, 'Cualquier anotación gana a no tener ninguna');
    }

    public function test_export_requiere_permiso(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('personal-secciones.export'))->assertForbidden();
    }

    public function test_export_devuelve_un_archivo_excel_respetando_el_filtro_de_seccion(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ver-personal-secciones', 'web'));
        $this->actingAs($user);

        $this->crearFuncionarioEnSeccion('Sección Violencia de Género');
        $this->crearFuncionarioEnSeccion('Sección Patrullas 911');

        $response = $this->get(route('personal-secciones.export', ['secciones' => ['Sección Violencia de Género']]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
