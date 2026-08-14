<?php

namespace Tests\Feature;

use App\Models\ChatAdjunto;
use App\Models\ChatConversacion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('anexos');
    }

    protected function usuarioConAccesoAlChat(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ver-chat');

        return $user;
    }

    protected function crearConversacionPrivada(User $uno, User $otro): ChatConversacion
    {
        $conversacion = ChatConversacion::create(['tipo' => 'privada', 'creado_por' => $uno->id]);
        $conversacion->participantes()->createMany([
            ['user_id' => $uno->id],
            ['user_id' => $otro->id],
        ]);

        return $conversacion;
    }

    public function test_un_visitante_no_puede_acceder_al_chat(): void
    {
        $this->getJson(route('chat.sync'))->assertUnauthorized();
    }

    public function test_un_usuario_no_puede_leer_una_conversacion_de_la_que_no_participa(): void
    {
        $ajeno = $this->usuarioConAccesoAlChat();
        $propietario = User::factory()->create();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($propietario, $otro);

        $this->actingAs($ajeno)
            ->getJson(route('chat.conversaciones.show', $conversacion))
            ->assertNotFound();
    }

    public function test_un_usuario_no_puede_enviar_mensajes_a_una_conversacion_ajena(): void
    {
        $ajeno = $this->usuarioConAccesoAlChat();
        $propietario = User::factory()->create();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($propietario, $otro);

        $this->actingAs($ajeno)
            ->postJson(route('chat.mensajes.store', $conversacion), ['cuerpo' => 'Hola'])
            ->assertNotFound();
    }

    public function test_un_usuario_no_puede_descargar_un_adjunto_de_una_conversacion_ajena(): void
    {
        $ajeno = $this->usuarioConAccesoAlChat();
        $propietario = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($propietario, $otro);

        $mensaje = $conversacion->mensajes()->create(['user_id' => $propietario->id, 'cuerpo' => null]);
        $adjunto = $mensaje->adjuntos()->create([
            'nombre_original' => 'documento.pdf',
            'ruta' => 'chat/1/archivo.pdf',
            'mime' => 'application/pdf',
            'tamano' => 1000,
        ]);

        $this->actingAs($ajeno)
            ->get(route('chat.adjuntos.show', $adjunto))
            ->assertNotFound();
    }

    public function test_sync_solo_devuelve_mensajes_posteriores_al_id_indicado(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $primero = $conversacion->mensajes()->create(['user_id' => $otro->id, 'cuerpo' => 'Primero']);
        $segundo = $conversacion->mensajes()->create(['user_id' => $otro->id, 'cuerpo' => 'Segundo']);

        $respuesta = $this->actingAs($user)->getJson(route('chat.sync', [
            'conversacion' => $conversacion->id,
            'desde' => $primero->id,
        ]))->assertOk();

        $respuesta->assertJsonCount(1, 'mensajes')
            ->assertJsonPath('mensajes.0.id', $segundo->id);
    }

    public function test_marcar_leido_deja_los_no_leidos_en_cero_y_los_propios_no_cuentan(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $conversacion->mensajes()->create(['user_id' => $user->id, 'cuerpo' => 'Mensaje propio']);
        $conversacion->mensajes()->create(['user_id' => $otro->id, 'cuerpo' => 'Mensaje ajeno']);

        $antes = $this->actingAs($user)->getJson(route('chat.sync'))->assertOk();
        $antes->assertJsonPath('no_leidos_total', 1);

        $this->actingAs($user)
            ->postJson(route('chat.conversaciones.leido', $conversacion))
            ->assertOk();

        $despues = $this->actingAs($user)->getJson(route('chat.sync'))->assertOk();
        $despues->assertJsonPath('no_leidos_total', 0);
    }

    public function test_iniciar_una_privada_dos_veces_reusa_la_conversacion(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();

        $primera = $this->actingAs($user)->postJson(route('chat.conversaciones.store'), [
            'tipo' => 'privada',
            'usuarios' => [$otro->id],
        ])->assertCreated();

        $segunda = $this->actingAs($user)->postJson(route('chat.conversaciones.store'), [
            'tipo' => 'privada',
            'usuarios' => [$otro->id],
        ])->assertCreated();

        $this->assertSame(
            $primera->json('conversacion.id'),
            $segunda->json('conversacion.id')
        );
        $this->assertSame(1, ChatConversacion::query()
            ->where('tipo', 'privada')
            ->where('creado_por', $user->id)
            ->count());
    }

    public function test_un_mensaje_sin_cuerpo_y_sin_adjunto_no_es_valido(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $this->actingAs($user)
            ->postJson(route('chat.mensajes.store', $conversacion), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cuerpo']);
    }

    public function test_un_mensaje_solo_con_adjunto_es_valido(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $this->actingAs($user)
            ->post(route('chat.mensajes.store', $conversacion), [
                'adjuntos' => [UploadedFile::fake()->create('foto.webp', 50)],
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertDatabaseHas('chat_mensajes', ['chat_conversacion_id' => $conversacion->id, 'cuerpo' => null]);
        $this->assertDatabaseHas('chat_adjuntos', ['nombre_original' => 'foto.webp']);
    }

    public function test_un_adjunto_webp_es_aceptado(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $this->actingAs($user)
            ->post(route('chat.mensajes.store', $conversacion), [
                'cuerpo' => 'Mirá esta imagen',
                'adjuntos' => [UploadedFile::fake()->create('captura.webp', 50)],
            ], ['Accept' => 'application/json'])
            ->assertCreated();
    }

    public function test_una_extension_fuera_de_la_whitelist_es_rechazada(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $this->actingAs($user)
            ->post(route('chat.mensajes.store', $conversacion), [
                'cuerpo' => 'Archivo raro',
                'adjuntos' => [UploadedFile::fake()->create('virus.exe', 50)],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['adjuntos.0']);
    }

    public function test_un_adjunto_no_es_alcanzable_sin_autenticarse(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $envio = $this->actingAs($user)->post(route('chat.mensajes.store', $conversacion), [
            'adjuntos' => [UploadedFile::fake()->create('secreto.pdf', 50)],
        ], ['Accept' => 'application/json'])->assertCreated();

        $adjuntoId = $envio->json('mensaje.adjuntos.0.id');

        Storage::disk('anexos')->assertExists(ChatAdjunto::findOrFail($adjuntoId)->ruta);

        Auth::logout();
        $this->get(route('chat.adjuntos.show', $adjuntoId))->assertRedirect(route('login'));
    }

    public function test_un_participante_puede_descargar_el_adjunto(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = $this->usuarioConAccesoAlChat();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $envio = $this->actingAs($user)->post(route('chat.mensajes.store', $conversacion), [
            'adjuntos' => [UploadedFile::fake()->create('documento.pdf', 50)],
        ], ['Accept' => 'application/json'])->assertCreated();

        $adjuntoId = $envio->json('mensaje.adjuntos.0.id');

        $this->actingAs($otro)
            ->get(route('chat.adjuntos.show', $adjuntoId))
            ->assertOk();
    }

    public function test_puede_iniciar_un_grupo_con_varios_destinatarios(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $miembro1 = User::factory()->create();
        $miembro2 = User::factory()->create();

        $respuesta = $this->actingAs($user)->postJson(route('chat.conversaciones.store'), [
            'tipo' => 'grupo',
            'nombre' => 'Guardia CECOCO',
            'usuarios' => [$miembro1->id, $miembro2->id],
        ])->assertCreated();

        $respuesta->assertJsonPath('conversacion.tipo', 'grupo')
            ->assertJsonPath('conversacion.nombre', 'Guardia CECOCO');

        $conversacionId = $respuesta->json('conversacion.id');
        $this->assertSame(3, ChatConversacion::findOrFail($conversacionId)->participantes()->count());
    }

    public function test_un_grupo_sin_nombre_no_es_valido(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $miembro1 = User::factory()->create();
        $miembro2 = User::factory()->create();

        $this->actingAs($user)->postJson(route('chat.conversaciones.store'), [
            'tipo' => 'grupo',
            'usuarios' => [$miembro1->id, $miembro2->id],
        ])->assertUnprocessable()->assertJsonValidationErrors(['nombre']);
    }

    public function test_sync_marca_al_usuario_como_en_linea_para_el_otro_participante(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = $this->usuarioConAccesoAlChat();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        $this->actingAs($user)->getJson(route('chat.sync'))->assertOk();

        $respuesta = $this->actingAs($otro)->getJson(route('chat.sync'))->assertOk();

        $datos = collect($respuesta->json('conversaciones'))->firstWhere('id', $conversacion->id);
        $this->assertTrue($datos['en_linea']);
    }

    public function test_un_usuario_sin_actividad_reciente_aparece_offline(): void
    {
        $user = $this->usuarioConAccesoAlChat();
        $otro = $this->usuarioConAccesoAlChat();
        $conversacion = $this->crearConversacionPrivada($user, $otro);

        Cache::forget("chat.online.{$otro->id}");

        $respuesta = $this->actingAs($user)->getJson(route('chat.sync'))->assertOk();

        $datos = collect($respuesta->json('conversaciones'))->firstWhere('id', $conversacion->id);
        $this->assertFalse($datos['en_linea']);
    }
}
