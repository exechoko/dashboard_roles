<?php

namespace Tests\Feature;

use App\Mail\MensajesChatSinLeerMail;
use App\Models\ChatConversacion;
use App\Models\ChatParticipante;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AvisarChatMensajesSinLeerTest extends TestCase
{
    use DatabaseTransactions;

    protected function crearConversacionPrivada(User $uno, User $otro): ChatConversacion
    {
        $conversacion = ChatConversacion::create(['tipo' => 'privada', 'creado_por' => $uno->id]);
        $conversacion->participantes()->createMany([
            ['user_id' => $uno->id],
            ['user_id' => $otro->id],
        ]);

        return $conversacion;
    }

    protected function crearMensajeConAntiguedad(ChatConversacion $conversacion, User $autor, int $minutos): void
    {
        $mensaje = $conversacion->mensajes()->create(['user_id' => $autor->id, 'cuerpo' => 'Hola']);

        DB::table('chat_mensajes')->where('id', $mensaje->id)->update([
            'created_at' => now()->subMinutes($minutos),
        ]);
    }

    public function test_no_avisa_si_el_mensaje_sin_leer_es_reciente(): void
    {
        Mail::fake();

        $destinatario = User::factory()->create(['email' => 'destinatario@test.com']);
        $autor = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($destinatario, $autor);
        $this->crearMensajeConAntiguedad($conversacion, $autor, 5);

        $this->artisan('chat:avisar-no-leidos')->assertExitCode(0);

        // No usamos assertNothingSent(): los tests corren contra la base de dev real
        // (ver AGENTS/CLAUDE.md), que puede tener otros pendientes de aviso ajenos a
        // este test. Alcanza con confirmar que a NUESTRO destinatario no se le mandó.
        Mail::assertNotSent(MensajesChatSinLeerMail::class, function (MensajesChatSinLeerMail $mail) {
            return $mail->hasTo('destinatario@test.com');
        });
    }

    public function test_avisa_por_mail_cuando_el_mensaje_sin_leer_supera_el_umbral(): void
    {
        Mail::fake();

        $destinatario = User::factory()->create(['email' => 'destinatario@test.com']);
        $autor = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($destinatario, $autor);
        $this->crearMensajeConAntiguedad($conversacion, $autor, 45);

        $this->artisan('chat:avisar-no-leidos')->assertExitCode(0);

        Mail::assertSent(MensajesChatSinLeerMail::class, function (MensajesChatSinLeerMail $mail) use ($conversacion) {
            return $mail->hasTo('destinatario@test.com')
                && $mail->pendientes->first()['conversacion_id'] === $conversacion->id
                && $mail->pendientes->first()['no_leidos'] === 1;
        });

        $this->assertNotNull(
            ChatParticipante::where('chat_conversacion_id', $conversacion->id)
                ->where('user_id', $destinatario->id)
                ->value('aviso_no_leido_enviado_at')
        );
    }

    public function test_no_repite_el_aviso_si_ya_se_envio_uno_para_ese_pendiente(): void
    {
        Mail::fake();

        $destinatario = User::factory()->create(['email' => 'destinatario@test.com']);
        $autor = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($destinatario, $autor);
        $this->crearMensajeConAntiguedad($conversacion, $autor, 45);

        $this->artisan('chat:avisar-no-leidos');

        Mail::assertSent(MensajesChatSinLeerMail::class, function (MensajesChatSinLeerMail $mail) {
            return $mail->hasTo('destinatario@test.com');
        });

        // Reseteamos el fake para que la segunda corrida se pueda verificar aislada
        // del envío anterior (y de cualquier otro pendiente ajeno de la base de dev).
        Mail::fake();

        $this->artisan('chat:avisar-no-leidos');

        Mail::assertNotSent(MensajesChatSinLeerMail::class, function (MensajesChatSinLeerMail $mail) {
            return $mail->hasTo('destinatario@test.com');
        });
    }

    public function test_marcar_leido_libera_el_aviso_para_poder_avisar_de_nuevo(): void
    {
        $destinatario = $this->usuarioConAccesoAlChat();
        $autor = User::factory()->create();
        $conversacion = $this->crearConversacionPrivada($destinatario, $autor);
        $this->crearMensajeConAntiguedad($conversacion, $autor, 45);

        ChatParticipante::where('chat_conversacion_id', $conversacion->id)
            ->where('user_id', $destinatario->id)
            ->update(['aviso_no_leido_enviado_at' => now()]);

        $this->actingAs($destinatario)
            ->postJson(route('chat.conversaciones.leido', $conversacion))
            ->assertOk();

        $this->assertNull(
            ChatParticipante::where('chat_conversacion_id', $conversacion->id)
                ->where('user_id', $destinatario->id)
                ->value('aviso_no_leido_enviado_at')
        );
    }

    protected function usuarioConAccesoAlChat(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ver-chat');

        return $user;
    }
}
