<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConversorAudioControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function token(): string
    {
        return Str::random(32);
    }

    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->get(route('herramientas.conversor-audio.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_la_herramienta(): void
    {
        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->get(route('herramientas.conversor-audio.index'));

        $response->assertOk()
            ->assertViewIs('herramientas.conversor-audio')
            ->assertSee('Convertir a MP3');
    }

    public function test_la_conversion_requiere_un_archivo(): void
    {
        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->postJson(route('herramientas.conversor-audio.lote.archivo', ['token' => $this->token()]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');
    }

    public function test_rechaza_un_archivo_con_extension_no_permitida(): void
    {
        $archivo = UploadedFile::fake()->create('audio.mp3', 10);

        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->postJson(route('herramientas.conversor-audio.lote.archivo', ['token' => $this->token()]), [
                'archivo' => $archivo,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');
    }

    public function test_informa_error_cuando_no_se_puede_convertir_el_archivo(): void
    {
        // Contenido que no es un WAV válido: falla la conversión sin importar
        // si FFmpeg está instalado en la máquina que corre el test.
        $archivo = UploadedFile::fake()->createWithContent('modulacion.wav', 'esto no es audio');

        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->postJson(route('herramientas.conversor-audio.lote.archivo', ['token' => $this->token()]), [
                'archivo' => $archivo,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_registra_en_el_historial_una_conversion_fallida(): void
    {
        $usuario = User::firstOrCreate(['email' => 'admin@gmail.com'], User::factory()->raw());
        // Contenido inválido: falla la conversión sin depender de si FFmpeg
        // está instalado en la máquina que corre el test.
        $archivo = UploadedFile::fake()->createWithContent('modulacion.wav', 'esto no es audio');

        $response = $this->actingAs($usuario)
            ->postJson(route('herramientas.conversor-audio.lote.archivo', ['token' => $this->token()]), [
                'archivo' => $archivo,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('item.nombre_archivo', 'modulacion.wav')
            ->assertJsonPath('item.exito', false)
            ->assertJsonPath('item.usuario', trim($usuario->name . ' ' . $usuario->apellido));

        $this->assertDatabaseHas('historial_conversion_audios', [
            'user_id' => $usuario->id,
            'nombre_archivo' => 'modulacion.wav',
            'extension_original' => 'wav',
            'exito' => false,
        ]);
    }

    public function test_descargar_un_lote_sin_archivos_convertidos_devuelve_404(): void
    {
        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->getJson(route('herramientas.conversor-audio.lote.descargar', ['token' => $this->token()]));

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_descarga_el_unico_mp3_de_un_lote_sin_armar_zip(): void
    {
        $usuario = User::firstOrCreate(['email' => 'admin@gmail.com'], User::factory()->raw());
        $token = $this->token();
        $directorio = "conversor_audio_temp/{$usuario->id}/{$token}";

        Storage::disk('local')->put($directorio . '/audio.mp3', 'contenido-mp3-de-prueba');

        $response = $this->actingAs($usuario)
            ->get(route('herramientas.conversor-audio.lote.descargar', ['token' => $token]));

        $response->assertOk();
        $this->assertStringContainsString('audio.mp3', $response->headers->get('Content-Disposition'));

        Storage::disk('local')->deleteDirectory("conversor_audio_temp/{$usuario->id}");
    }
}
