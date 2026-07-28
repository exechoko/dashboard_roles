<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ArchivoHashControllerTest extends TestCase
{
    use DatabaseTransactions;

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
            ->assertViewIs('herramientas.hash-archivo')
            ->assertSee('Copiar para informe');
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

    public function test_puede_registrar_el_hash_sin_subir_el_archivo(): void
    {
        $datos = [
            'nombre_archivo' => 'evidencia.bin',
            'cifrado_aplicado' => 'SHA-256',
            'hash' => 'd7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592',
        ];

        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->postJson(route('herramientas.hash.historial.registrar'), $datos);

        $response->assertCreated()
            ->assertJsonPath('item.nombre_archivo', 'evidencia.bin')
            ->assertJsonPath('item.cifrado_aplicado', 'SHA-256')
            ->assertJsonPath('item.hash', $datos['hash']);

        $this->assertDatabaseHas('historial_hash_archivos', [
            'nombre_archivo' => 'evidencia.bin',
            'cifrado_aplicado' => 'SHA-256',
            'hash' => $datos['hash'],
        ]);
    }

    public function test_rechaza_un_hash_con_formato_invalido(): void
    {
        $response = $this->actingAs(User::factory()->make(['email' => 'admin@gmail.com']))
            ->postJson(route('herramientas.hash.historial.registrar'), [
                'nombre_archivo' => 'evidencia.bin',
                'cifrado_aplicado' => 'SHA-256',
                'hash' => 'hash-invalido',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('hash');
    }
}
