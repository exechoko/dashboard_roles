<?php

namespace Tests\Feature;

use App\Jobs\ProcessChatbotMessage;
use App\Models\Camara;
use App\Models\ChatbotConversation;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\MailBuzon;
use App\Models\Recurso;
use App\Models\Sitio;
use App\Models\User;
use App\Services\Chatbot\BuscadorDependencias;
use App\Services\Chatbot\CatalogoConsultas;
use App\Services\ChatbotContentSanitizer;
use App\Services\OpenCodeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatbotConsultasDatosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_catalogo_solo_incluye_las_consultas_habilitadas_por_permisos(): void
    {
        $usuario = $this->usuarioCon(['ver-equipo']);
        $catalogo = new CatalogoConsultas();

        $nombres = $catalogo->disponiblesPara($usuario)->keys()->all();

        $this->assertContains('equipos_por_estado', $nombres);
        $this->assertContains('equipos_por_tipo_terminal', $nombres);
        $this->assertNotContains('buzon_mensajes', $nombres);
        $this->assertNotContains('movimientos_equipo', $nombres);
        $this->assertStringContainsString('equipos_por_estado', $catalogo->describirPara($usuario));
    }

    public function test_un_usuario_sin_permisos_no_tiene_ninguna_consulta_disponible(): void
    {
        $usuario = $this->usuarioCon([]);
        $catalogo = new CatalogoConsultas();

        $this->assertTrue($catalogo->disponiblesPara($usuario)->isEmpty());
        $this->assertSame('', $catalogo->describirPara($usuario));
        $this->assertNull($catalogo->resolver($usuario, 'equipos_por_estado'));
    }

    public function test_no_resuelve_una_consulta_para_la_que_el_usuario_no_tiene_permiso(): void
    {
        $usuario = $this->usuarioCon(['ver-equipo']);

        $this->assertNull((new CatalogoConsultas())->resolver($usuario, 'buzon_mensajes'));
        $this->assertNotNull((new CatalogoConsultas())->resolver($usuario, 'equipos_por_estado'));
    }

    public function test_equipos_por_estado_informa_el_total_operativo(): void
    {
        $usuario = $this->usuarioCon(['ver-equipo']);
        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_por_estado');

        $esperado = number_format(Equipo::query()->operativo()->count(), 0, ',', '.');
        $respuesta = $consulta->ejecutar($usuario, ['estado' => 'en funcionamiento']);

        $this->assertStringContainsString($esperado, $respuesta);
        $this->assertStringContainsString('operativos', $respuesta);
        $this->assertStringContainsString('(/equipos)', $respuesta);
    }

    public function test_equipos_por_estado_avisa_cuando_el_estado_no_existe(): void
    {
        $usuario = $this->usuarioCon(['ver-equipo']);
        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_por_estado');

        $respuesta = $consulta->ejecutar($usuario, ['estado' => 'melancolico']);

        $this->assertStringContainsString('No encontré un estado llamado "melancolico"', $respuesta);
    }

    public function test_movimientos_equipo_lista_las_asignaciones_de_la_flota(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $issi = '9' . random_int(1000000, 9999999);

        $equipo = Equipo::create(['issi' => $issi, 'tei' => 'TEI-' . $issi]);
        $destino = Destino::create(['nombre' => 'Destino de prueba ' . $issi]);

        $recurso = new Recurso();
        $recurso->nombre = 'MOVIL PRUEBA ' . $issi;
        $recurso->destino_id = $destino->id;
        $recurso->save();

        FlotaGeneral::create([
            'equipo_id' => $equipo->id,
            'recurso_id' => $recurso->id,
            'destino_id' => $destino->id,
            'fecha_asignacion' => '2026-01-15',
        ]);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'movimientos_equipo');
        $respuesta = $consulta->ejecutar($usuario, ['issi' => $issi]);

        $this->assertStringContainsString('1 movimiento', $respuesta);
        $this->assertStringContainsString('MOVIL PRUEBA ' . $issi, $respuesta);
        $this->assertStringContainsString('Destino de prueba ' . $issi, $respuesta);
        $this->assertStringContainsString('15/01/2026', $respuesta);
        $this->assertStringContainsString('asignación vigente', $respuesta);
    }

    public function test_movimientos_equipo_avisa_si_no_encuentra_el_equipo(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $consulta = (new CatalogoConsultas())->resolver($usuario, 'movimientos_equipo');

        $respuesta = $consulta->ejecutar($usuario, ['issi' => 'inexistente-000']);

        $this->assertStringContainsString('No encontré ningún equipo', $respuesta);
    }

    public function test_movimientos_equipo_pide_el_identificador_si_no_lo_recibe(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $consulta = (new CatalogoConsultas())->resolver($usuario, 'movimientos_equipo');

        $this->assertStringContainsString(
            'Necesito el ISSI o el TEI',
            $consulta->ejecutar($usuario, [])
        );
    }

    public function test_camaras_por_localidad_cuenta_las_camaras_de_esa_localidad(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $localidad = 'Localidad Prueba ' . random_int(100000, 999999);

        $sitio = Sitio::create(['nombre' => 'Sitio ' . $localidad, 'localidad' => $localidad]);
        Camara::create(['nombre' => 'CAM-1', 'sitio_id' => $sitio->id]);
        Camara::create(['nombre' => 'CAM-2', 'sitio_id' => $sitio->id]);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'camaras_por_localidad');
        $respuesta = $consulta->ejecutar($usuario, ['localidad' => $localidad]);

        $this->assertStringContainsString('2 cámaras registradas', $respuesta);
        $this->assertStringContainsString('1 sitio', $respuesta);
        $this->assertStringContainsString('(/camaras)', $respuesta);
    }

    public function test_camaras_por_localidad_avisa_si_la_localidad_no_existe(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $consulta = (new CatalogoConsultas())->resolver($usuario, 'camaras_por_localidad');

        $respuesta = $consulta->ejecutar($usuario, ['localidad' => 'Ciudad Gotica']);

        $this->assertStringContainsString('No encontré la localidad "Ciudad Gotica"', $respuesta);
    }

    public function test_buzon_mensajes_ignora_los_buzones_de_otros_roles(): void
    {
        $usuario = $this->usuarioCon(['ver-visor-mails']);
        $rolAjeno = Role::findOrCreate('rol-ajeno-chatbot-' . random_int(1000, 9999), 'web');

        $buzon = MailBuzon::create([
            'nombre' => 'Buzon Ajeno Prueba',
            'carpeta' => 'buzon-ajeno-prueba',
            'email' => 'ajeno-prueba@test.local',
            'role_id' => $rolAjeno->id,
            'activo' => true,
        ]);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'buzon_mensajes');
        $respuesta = $consulta->ejecutar($usuario, ['buzon' => $buzon->nombre]);

        $this->assertStringContainsString('No encontré un buzón llamado', $respuesta);
    }

    public function test_el_job_ejecuta_la_consulta_que_pide_el_modelo(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $localidad = 'Localidad Job ' . random_int(100000, 999999);

        $sitio = Sitio::create(['nombre' => 'Sitio ' . $localidad, 'localidad' => $localidad]);
        Camara::create(['nombre' => 'CAM-JOB', 'sitio_id' => $sitio->id]);

        $assistantMessage = $this->conversacionCon($usuario, '¿Cuántas cámaras hay en ' . $localidad . '?');

        $openCode = $this->mock(OpenCodeService::class);
        $openCode->expects('createSession')->once()->andReturn(['id' => 'ses_datos']);
        $openCode->expects('sendMessage')
            ->once()
            ->withArgs(fn (string $sesion, string $prompt, string $catalogo): bool => str_contains($catalogo, 'camaras_por_localidad'))
            ->andReturn(json_encode([
                'consulta' => 'camaras_por_localidad',
                'parametros' => ['localidad' => $localidad],
            ]));

        $this->ejecutarJob($assistantMessage->id, $openCode);

        $contenido = $assistantMessage->fresh()->content;
        $this->assertSame('completed', $assistantMessage->fresh()->status);
        $this->assertStringContainsString('1 cámara registrada', $contenido);
        $this->assertStringContainsString($localidad, $contenido);
    }

    public function test_el_job_no_ejecuta_una_consulta_para_la_que_el_usuario_no_tiene_permiso(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $assistantMessage = $this->conversacionCon($usuario, '¿Cuántos mails tiene Mesa de Entradas?');

        $openCode = $this->mock(OpenCodeService::class);
        $openCode->expects('createSession')->once()->andReturn(['id' => 'ses_denegado']);
        $openCode->expects('sendMessage')
            ->once()
            ->andReturn('{"consulta":"buzon_mensajes","parametros":{"buzon":"Mesa de Entradas"}}');

        $this->ejecutarJob($assistantMessage->id, $openCode);

        $this->assertStringContainsString(
            'No tengo habilitada ninguna consulta',
            $assistantMessage->fresh()->content
        );
    }

    public function test_el_job_guarda_como_texto_una_respuesta_que_no_pide_datos(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $assistantMessage = $this->conversacionCon($usuario, '¿Cómo cargo una cámara?');

        $openCode = $this->mock(OpenCodeService::class);
        $openCode->expects('createSession')->once()->andReturn(['id' => 'ses_texto']);
        $openCode->expects('sendMessage')->once()->andReturn('Entrá a [Cámaras](/camaras) y tocá Nueva.');

        $this->ejecutarJob($assistantMessage->id, $openCode);

        $this->assertSame(
            'Entrá a [Cámaras](/camaras) y tocá Nueva.',
            $assistantMessage->fresh()->content
        );
    }

    public function test_el_job_descarta_un_nombre_de_consulta_inventado(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $assistantMessage = $this->conversacionCon($usuario, '¿Cuántos patrulleros hay?');

        $openCode = $this->mock(OpenCodeService::class);
        $openCode->expects('createSession')->once()->andReturn(['id' => 'ses_inventado']);
        $openCode->expects('sendMessage')
            ->once()
            ->andReturn('```json' . "\n" . '{"consulta":"patrulleros_totales","parametros":{}}' . "\n" . '```');

        $this->ejecutarJob($assistantMessage->id, $openCode);

        $this->assertStringContainsString(
            'No tengo habilitada ninguna consulta',
            $assistantMessage->fresh()->content
        );
    }

    public function test_el_catalogo_expone_las_consultas_por_dependencia_segun_el_permiso(): void
    {
        $catalogo = new CatalogoConsultas();

        $conFlota = $catalogo->disponiblesPara($this->usuarioCon(['ver-flota']))->keys()->all();
        $this->assertContains('equipos_por_dependencia', $conFlota);
        $this->assertContains('equipos_de_recurso', $conFlota);
        $this->assertContains('recursos_por_dependencia', $conFlota);
        $this->assertContains('resumen_dependencia', $conFlota);
        $this->assertNotContains('camaras_por_dependencia', $conFlota);

        $conCamaras = $catalogo->disponiblesPara($this->usuarioCon(['ver-camara']))->keys()->all();
        $this->assertContains('camaras_por_dependencia', $conCamaras);
        $this->assertNotContains('equipos_por_dependencia', $conCamaras);
    }

    public function test_equipos_por_dependencia_suma_las_dependencias_a_cargo(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $sufijo = 'Chatbot ' . random_int(100000, 999999);

        $padre = Destino::create(['nombre' => 'Departamental ' . $sufijo, 'tipo' => 'departamental']);
        $hija = Destino::create([
            'nombre' => 'Comisaria Hija ' . $sufijo,
            'tipo' => 'comisaria',
            'parent_id' => $padre->id,
        ]);

        $this->asignarEquipo($padre, 'Base ' . $sufijo);
        $this->asignarEquipo($hija, 'Movil ' . $sufijo);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_por_dependencia');
        $respuesta = $consulta->ejecutar($usuario, ['dependencia' => 'Departamental ' . $sufijo]);

        $this->assertStringContainsString('2 equipos asignados', $respuesta);
        $this->assertStringContainsString('incluyendo 1 dependencia', $respuesta);
        $this->assertStringContainsString('Base ' . $sufijo, $respuesta);
        $this->assertStringContainsString('Movil ' . $sufijo, $respuesta);
    }

    public function test_equipos_por_dependencia_puede_excluir_las_dependencias_a_cargo(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $sufijo = 'Chatbot ' . random_int(100000, 999999);

        $padre = Destino::create(['nombre' => 'Departamental ' . $sufijo, 'tipo' => 'departamental']);
        $hija = Destino::create([
            'nombre' => 'Comisaria Hija ' . $sufijo,
            'tipo' => 'comisaria',
            'parent_id' => $padre->id,
        ]);

        $this->asignarEquipo($padre, 'Base ' . $sufijo);
        $this->asignarEquipo($hija, 'Movil ' . $sufijo);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_por_dependencia');
        $respuesta = $consulta->ejecutar($usuario, [
            'dependencia' => 'Departamental ' . $sufijo,
            'incluir_dependientes' => 'no',
        ]);

        $this->assertStringContainsString('1 equipo asignado', $respuesta);
        $this->assertStringNotContainsString('incluyendo', $respuesta);
        $this->assertStringNotContainsString('Movil ' . $sufijo, $respuesta);
    }

    public function test_una_dependencia_ambigua_devuelve_las_opciones_en_vez_de_un_numero(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $sufijo = 'Chatbot ' . random_int(100000, 999999);

        Destino::create(['nombre' => 'Comisaria Alfa ' . $sufijo, 'tipo' => 'comisaria']);
        Destino::create(['nombre' => 'Comisaria Beta ' . $sufijo, 'tipo' => 'comisaria']);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_por_dependencia');
        $respuesta = $consulta->ejecutar($usuario, ['dependencia' => $sufijo]);

        $this->assertStringContainsString('Hay varias dependencias', $respuesta);
        $this->assertStringContainsString('Comisaria Alfa ' . $sufijo, $respuesta);
        $this->assertStringContainsString('Comisaria Beta ' . $sufijo, $respuesta);
    }

    public function test_el_nombre_exacto_gana_sobre_las_coincidencias_parciales(): void
    {
        $sufijo = 'Chatbot ' . random_int(100000, 999999);
        $exacta = Destino::create(['nombre' => 'Division ' . $sufijo, 'tipo' => 'division']);
        Destino::create(['nombre' => 'Division ' . $sufijo . ' Anexo', 'tipo' => 'division']);

        $buscador = app(BuscadorDependencias::class);
        $coincidencias = $buscador->coincidencias('Division ' . $sufijo);

        $this->assertCount(2, $coincidencias);
        $this->assertNull($buscador->mensajeDeAmbiguedad('Division ' . $sufijo, $coincidencias));
        $this->assertSame($exacta->id, $coincidencias->first()->id);
    }

    public function test_encuentra_la_dependencia_aunque_sobren_palabras(): void
    {
        $sufijo = 'Chatbot ' . random_int(100000, 999999);
        $tecnica = Destino::create(['nombre' => 'Seccion Tecnica ' . $sufijo, 'tipo' => 'seccion']);
        Destino::create(['nombre' => 'Division 911 ' . $sufijo, 'tipo' => 'division']);

        $buscador = app(BuscadorDependencias::class);
        $coincidencias = $buscador->coincidencias('Seccion Tecnica del 911 ' . $sufijo);

        $this->assertSame($tecnica->id, $coincidencias->first()->id);
        $this->assertNull($buscador->mensajeDeAmbiguedad('Seccion Tecnica del 911', $coincidencias));
    }

    public function test_entiende_abreviaturas_y_ordinales_en_numeros(): void
    {
        $sufijo = 'Chatbot ' . random_int(100000, 999999);
        $sexta = Destino::create(['nombre' => 'Comisaría Sexta (6ª) ' . $sufijo, 'tipo' => 'comisaria']);
        Destino::create(['nombre' => 'Comisaría Décimo sexta (16ª) ' . $sufijo, 'tipo' => 'comisaria']);

        $buscador = app(BuscadorDependencias::class);
        $coincidencias = $buscador->coincidencias('Cria. 6ta ' . $sufijo);

        $this->assertSame($sexta->id, $coincidencias->first()->id);
        $this->assertNull($buscador->mensajeDeAmbiguedad('Cria. 6ta', $coincidencias));
    }

    public function test_no_confunde_la_sexta_con_la_decimo_sexta(): void
    {
        $sufijo = 'Chatbot ' . random_int(100000, 999999);
        Destino::create(['nombre' => 'Comisaría Sexta (6ª) ' . $sufijo, 'tipo' => 'comisaria']);
        $decimoSexta = Destino::create(['nombre' => 'Comisaría Décimo sexta (16ª) ' . $sufijo, 'tipo' => 'comisaria']);

        $coincidencias = app(BuscadorDependencias::class)->coincidencias('Cria 16 ' . $sufijo);

        $this->assertCount(1, $coincidencias);
        $this->assertSame($decimoSexta->id, $coincidencias->first()->id);
    }

    public function test_avisa_cuando_la_dependencia_no_existe(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_por_dependencia');

        $respuesta = $consulta->ejecutar($usuario, ['dependencia' => 'Comisaria de Ciudad Gotica']);

        $this->assertStringContainsString('No encontré ninguna dependencia', $respuesta);
    }

    public function test_camaras_por_dependencia_cuenta_las_camaras_a_cargo(): void
    {
        $usuario = $this->usuarioCon(['ver-camara']);
        $sufijo = 'Chatbot ' . random_int(100000, 999999);

        $dependencia = Destino::create(['nombre' => 'Comisaria Camaras ' . $sufijo, 'tipo' => 'comisaria']);
        $this->camaraDe($dependencia, 'CAM-A');
        $this->camaraDe($dependencia, 'CAM-B', '2026-01-10');

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'camaras_por_dependencia');
        $respuesta = $consulta->ejecutar($usuario, ['dependencia' => 'Comisaria Camaras ' . $sufijo]);

        $this->assertStringContainsString('2 cámaras a cargo', $respuesta);
        $this->assertStringContainsString('1 instaladas y 1 desinstaladas', $respuesta);
    }

    public function test_equipos_de_recurso_lista_los_equipos_asignados(): void
    {
        $usuario = $this->usuarioCon(['ver-flota']);
        $sufijo = 'Chatbot ' . random_int(100000, 999999);

        $dependencia = Destino::create(['nombre' => 'Comisaria Recurso ' . $sufijo, 'tipo' => 'comisaria']);
        $equipo = $this->asignarEquipo($dependencia, 'Movil ' . $sufijo);

        $consulta = (new CatalogoConsultas())->resolver($usuario, 'equipos_de_recurso');
        $respuesta = $consulta->ejecutar($usuario, ['recurso' => 'Movil ' . $sufijo]);

        $this->assertStringContainsString('1 equipo asignado', $respuesta);
        $this->assertStringContainsString('ISSI ' . $equipo->issi, $respuesta);
        $this->assertStringContainsString('Comisaria Recurso ' . $sufijo, $respuesta);
    }

    public function test_el_resumen_de_dependencia_omite_lo_que_el_usuario_no_puede_ver(): void
    {
        $sufijo = 'Chatbot ' . random_int(100000, 999999);
        $dependencia = Destino::create(['nombre' => 'Division Resumen ' . $sufijo, 'tipo' => 'division']);
        $this->asignarEquipo($dependencia, 'Base ' . $sufijo);
        $this->camaraDe($dependencia, 'CAM-R');

        $soloFlota = $this->usuarioCon(['ver-flota']);
        $respuestaFlota = (new CatalogoConsultas())
            ->resolver($soloFlota, 'resumen_dependencia')
            ->ejecutar($soloFlota, ['dependencia' => 'Division Resumen ' . $sufijo]);

        $this->assertStringContainsString('Equipos de comunicación asignados: 1', $respuestaFlota);
        $this->assertStringNotContainsString('Cámaras a cargo', $respuestaFlota);

        $soloCamaras = $this->usuarioCon(['ver-camara']);
        $respuestaCamaras = (new CatalogoConsultas())
            ->resolver($soloCamaras, 'resumen_dependencia')
            ->ejecutar($soloCamaras, ['dependencia' => 'Division Resumen ' . $sufijo]);

        $this->assertStringContainsString('Cámaras a cargo: 1', $respuestaCamaras);
        $this->assertStringNotContainsString('Equipos de comunicación asignados', $respuestaCamaras);
    }

    /**
     * Crea una cámara a cargo de la dependencia. `destino_id` y
     * `fecha_desintalacion` no son asignables en masa, así que se cargan
     * atributo por atributo.
     */
    private function camaraDe(Destino $dependencia, string $nombre, ?string $desinstalacion = null): Camara
    {
        $camara = new Camara();
        $camara->nombre = $nombre;
        $camara->destino_id = $dependencia->id;
        $camara->fecha_desintalacion = $desinstalacion;
        $camara->save();

        return $camara;
    }

    /**
     * Crea un equipo asignado a un recurso nuevo de la dependencia indicada.
     */
    private function asignarEquipo(Destino $dependencia, string $nombreRecurso): Equipo
    {
        $issi = '9' . random_int(1000000, 9999999);
        $equipo = Equipo::create(['issi' => $issi, 'tei' => 'TEI-' . $issi]);

        $recurso = new Recurso();
        $recurso->nombre = $nombreRecurso;
        $recurso->destino_id = $dependencia->id;
        $recurso->save();

        FlotaGeneral::create([
            'equipo_id' => $equipo->id,
            'recurso_id' => $recurso->id,
            'destino_id' => $dependencia->id,
            'fecha_asignacion' => '2026-02-01',
        ]);

        return $equipo;
    }

    /**
     * @param  array<int, string>  $permisos
     */
    private function usuarioCon(array $permisos): User
    {
        $usuario = User::factory()->create();

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
        }

        return $usuario->fresh();
    }

    private function conversacionCon(User $usuario, string $pregunta): \App\Models\ChatbotMessage
    {
        $conversation = ChatbotConversation::create([
            'user_id' => $usuario->id,
            'title' => 'Consulta de datos',
        ]);

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $pregunta,
            'status' => 'completed',
        ]);

        return $conversation->messages()->create([
            'role' => 'assistant',
            'status' => 'pending',
        ]);
    }

    private function ejecutarJob(int $assistantMessageId, OpenCodeService $openCode): void
    {
        (new ProcessChatbotMessage($assistantMessageId))->handle(
            $openCode,
            new CatalogoConsultas(),
            new ChatbotContentSanitizer()
        );
    }
}
