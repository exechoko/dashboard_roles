<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardWorkersStatusTest extends TestCase
{
    use DatabaseTransactions;

    // No se testea el render de '/' (HomeController::index): agrega varias
    // consultas pesadas y llamadas externas (LibreNMS, CECOCO) que la hacen
    // demasiado lenta y frágil para un test — no relacionadas con este cambio,
    // que solo agrega un bloque estático más al mismo layout ya probado.

    public function test_el_endpoint_de_estado_incluye_los_contadores_de_la_cola_mbox(): void
    {
        $admin = User::factory()->create();

        $respuesta = $this->actingAs($admin)->getJson(route('api.dashboard.workers-status'));

        $respuesta->assertOk();
        $respuesta->assertJsonStructure(['mbox_worker_activo', 'mbox_pendientes', 'mbox_procesando']);
    }
}
