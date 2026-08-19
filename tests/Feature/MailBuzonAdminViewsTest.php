<?php

namespace Tests\Feature;

use App\Jobs\IndexarArchivoMbox;
use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MailBuzonAdminViewsTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioAdministrador(): User
    {
        $permiso = Permission::firstOrCreate(['name' => 'administrar-visor-mails', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo($permiso);

        return $user;
    }

    public function test_las_vistas_de_administracion_de_buzones_renderizan(): void
    {
        $admin = $this->usuarioAdministrador();
        $buzon = MailBuzon::create(['nombre' => 'Buzón Vistas', 'carpeta' => 'test_vistas_'.uniqid(), 'activo' => true]);

        $this->actingAs($admin)->get(route('herramientas.mails.buzones.index'))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.detectar-oficinas'))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.create'))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.edit', $buzon))->assertOk();
        $this->actingAs($admin)->get(route('herramientas.mails.buzones.archivos', $buzon))->assertOk();
    }

    public function test_el_boton_indexar_encola_el_job_en_la_cola_mbox_sin_error_fatal(): void
    {
        // Regresión: IndexarArchivoMbox llegó a redeclarar $connection/$queue como
        // propiedades de clase, propiedades que ya declara el trait Queueable —
        // en producción eso es un fatal de PHP ("propiedad incompatible") apenas
        // se carga la clase. Este test fuerza esa carga real vía dispatch().
        Queue::fake();

        $admin = $this->usuarioAdministrador();
        $buzon = MailBuzon::create(['nombre' => 'Buzón Indexar', 'carpeta' => 'test_indexar_'.uniqid(), 'activo' => true]);
        $archivo = MailArchivo::create([
            'buzon_id' => $buzon->id,
            'nombre_archivo' => 'prueba.mbox',
            'ruta_absoluta' => base_path('tests/Fixtures/mbox/prueba.mbox'),
            'tamano_bytes' => filesize(base_path('tests/Fixtures/mbox/prueba.mbox')),
            'estado' => 'indexado',
        ]);

        $this->actingAs($admin)
            ->post(route('herramientas.mails.buzones.archivos.indexar', $archivo))
            ->assertRedirect();

        Queue::assertPushedOn('mbox', IndexarArchivoMbox::class);
    }
}
