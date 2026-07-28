<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ArchivoHashControllerTest extends TestCase
{
    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->get(route('herramientas.hash.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_la_herramienta(): void
    {
        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->get(route('herramientas.hash.index'));

        $response->assertOk()
            ->assertViewIs('herramientas.hash-archivo');
    }

    public function test_un_usuario_puede_obtener_el_hash_de_un_archivo(): void
    {
        $archivo = UploadedFile::fake()->createWithContent(
            'evidencia.cualquier-formato',
            'The quick brown fox jumps over the lazy dog'
        );

        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->post(route('herramientas.hash.calcular'), ['archivo' => $archivo]);

        $response->assertOk()
            ->assertViewHas('resultado', function (array $resultado): bool {
                return $resultado['nombre'] === 'evidencia.cualquier-formato'
                    && $resultado['tamano'] === 43
                    && $resultado['hash'] === 'd7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592';
            });
    }

    public function test_el_calculo_requiere_un_archivo(): void
    {
        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->post(route('herramientas.hash.calcular'));

        $response->assertSessionHasErrors('archivo');
    }
}
