<?php

namespace Tests\Feature;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Models\User;
use App\Services\Mbox\MboxIndexador;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Los filtros "De", "Para" y "Asunto" usan MATCH AGAINST sobre índices
 * FULLTEXT de InnoDB, que solo son consultables sobre datos confirmados
 * (COMMIT): por eso esta clase no usa DatabaseTransactions (el indexado
 * quedaría en una transacción sin confirmar y la búsqueda no vería nada) y
 * en cambio borra a mano lo que crea en tearDown.
 */
class MailFiltroFulltextTest extends TestCase
{
    private ?MailBuzon $buzon = null;

    private ?User $usuario = null;

    protected function tearDown(): void
    {
        if ($this->buzon) {
            $this->buzon->mensajes()->delete();
            $this->buzon->archivos()->delete();
            $this->buzon->delete();
        }

        $this->usuario?->delete();

        parent::tearDown();
    }

    private function indexarFixtureYUsuario(): void
    {
        $role = Role::firstOrCreate(['name' => 'rol_test_mbox_fulltext', 'guard_name' => 'web']);
        $permiso = Permission::firstOrCreate(['name' => 'ver-visor-mails', 'guard_name' => 'web']);
        $role->givePermissionTo($permiso);

        $this->buzon = MailBuzon::create([
            'nombre' => 'Buzón Fulltext',
            'carpeta' => 'test_fulltext_'.uniqid(),
            'role_id' => $role->id,
            'activo' => true,
        ]);

        $archivo = MailArchivo::create([
            'buzon_id' => $this->buzon->id,
            'nombre_archivo' => 'prueba.mbox',
            'ruta_absoluta' => base_path('tests/Fixtures/mbox/prueba.mbox'),
            'tamano_bytes' => filesize(base_path('tests/Fixtures/mbox/prueba.mbox')),
            'estado' => 'pendiente',
        ]);

        app(MboxIndexador::class)->indexar($archivo);

        $this->usuario = User::factory()->create();
        $this->usuario->assignRole($role);
    }

    public function test_el_filtro_de_busca_por_nombre_o_email_del_remitente(): void
    {
        $this->indexarFixtureYUsuario();

        $respuesta = $this->actingAs($this->usuario)
            ->get(route('herramientas.mails.index', ['buzon_id' => $this->buzon->id, 'de' => 'juan']));

        $respuesta->assertOk();
        $respuesta->assertSee('Prueba con acentos');
        $respuesta->assertDontSee('Notificacion con HTML');
    }

    public function test_el_filtro_para_busca_en_destinatarios_y_copia(): void
    {
        $this->indexarFixtureYUsuario();

        $respuesta = $this->actingAs($this->usuario)
            ->get(route('herramientas.mails.index', ['buzon_id' => $this->buzon->id, 'para' => 'copia']));

        $respuesta->assertOk();
        $respuesta->assertSee('Notificacion con HTML');
        $respuesta->assertDontSee('Mensaje con adjunto');
    }

    public function test_el_filtro_asunto_busca_por_palabras_del_asunto(): void
    {
        $this->indexarFixtureYUsuario();

        $respuesta = $this->actingAs($this->usuario)
            ->get(route('herramientas.mails.index', ['buzon_id' => $this->buzon->id, 'asunto' => 'adjunto']));

        $respuesta->assertOk();
        $respuesta->assertSee('Mensaje con adjunto');
        $respuesta->assertDontSee('Notificacion con HTML');
    }
}
