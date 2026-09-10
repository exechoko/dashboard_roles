<?php

namespace Tests\Feature;

use App\Models\Recurso;
use App\Models\RecursoBitacora;
use App\Models\RecursoBitacoraAdjunto;
use App\Models\RecursoBitacoraSolicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecursoBitacoraModelosTest extends TestCase
{
    use DatabaseTransactions;

    private function recurso(): Recurso
    {
        return Recurso::query()->whereNotNull('vehiculo_id')->firstOrFail();
    }

    public function test_categorias_y_label(): void
    {
        $this->assertArrayHasKey('mantenimiento', RecursoBitacora::CATEGORIAS);
        $this->assertCount(9, RecursoBitacora::CATEGORIAS);

        $entrada = new RecursoBitacora(['categoria' => 'chapa_pintura']);
        $this->assertSame('Chapa y pintura', $entrada->categoriaLabel());
    }

    public function test_scopes_abiertas_y_ordenadas(): void
    {
        $recurso = $this->recurso();
        $user = User::factory()->create();

        $vieja = RecursoBitacora::factory()->create([
            'recurso_id' => $recurso->id, 'user_id' => $user->id,
            'fecha_hora' => '2099-01-01 08:00', 'estado' => null,
        ]);
        $abierta = RecursoBitacora::factory()->abierta()->create([
            'recurso_id' => $recurso->id, 'user_id' => $user->id,
            'fecha_hora' => '2099-06-01 08:00',
        ]);

        $abiertas = RecursoBitacora::abiertas()->where('recurso_id', $recurso->id)->pluck('id');
        $this->assertTrue($abiertas->contains($abierta->id));
        $this->assertFalse($abiertas->contains($vieja->id));

        $ordenadas = RecursoBitacora::where('recurso_id', $recurso->id)
            ->whereIn('id', [$vieja->id, $abierta->id])
            ->ordenadas()->pluck('id')->all();
        $this->assertSame([$abierta->id, $vieja->id], $ordenadas);
    }

    public function test_relaciones_del_recurso(): void
    {
        $recurso = $this->recurso();
        $user = User::factory()->create();

        RecursoBitacora::factory()->create(['recurso_id' => $recurso->id, 'user_id' => $user->id, 'fecha_hora' => '2099-02-01 10:00']);
        $ultima = RecursoBitacora::factory()->abierta()->create(['recurso_id' => $recurso->id, 'user_id' => $user->id, 'fecha_hora' => '2099-07-01 10:00']);

        $recurso->load('bitacora', 'bitacoraAbiertas', 'ultimaBitacora');

        $this->assertGreaterThanOrEqual(2, $recurso->bitacora->count());
        $this->assertSame($ultima->id, $recurso->ultimaBitacora->id);
        $this->assertTrue($recurso->bitacoraAbiertas->contains('id', $ultima->id));
        $this->assertTrue($recurso->tieneBitacoraAbierta());
    }

    public function test_solicitud_scopes(): void
    {
        $recurso = $this->recurso();
        $user = User::factory()->create();
        $entrada = RecursoBitacora::factory()->create(['recurso_id' => $recurso->id, 'user_id' => $user->id]);

        $pendiente = RecursoBitacoraSolicitud::create([
            'bitacora_id' => $entrada->id, 'tipo' => RecursoBitacoraSolicitud::TIPO_EDICION,
            'cambios' => ['descripcion' => 'nuevo texto'], 'user_id' => $user->id,
        ]);
        $rechazada = RecursoBitacoraSolicitud::create([
            'bitacora_id' => $entrada->id, 'tipo' => RecursoBitacoraSolicitud::TIPO_ELIMINACION,
            'estado' => RecursoBitacoraSolicitud::ESTADO_RECHAZADA, 'user_id' => $user->id,
        ]);

        $this->assertTrue(RecursoBitacoraSolicitud::pendientes()->pluck('id')->contains($pendiente->id));
        $this->assertTrue(RecursoBitacoraSolicitud::resueltas()->pluck('id')->contains($rechazada->id));
        $this->assertSame(['descripcion' => 'nuevo texto'], $pendiente->fresh()->cambios);
        $this->assertSame($entrada->id, $entrada->fresh()->solicitudPendiente->bitacora_id);
    }

    public function test_adjunto_tipo(): void
    {
        $img = new RecursoBitacoraAdjunto(['mime_type' => 'image/jpeg']);
        $pdf = new RecursoBitacoraAdjunto(['mime_type' => 'application/pdf']);
        $docx = new RecursoBitacoraAdjunto(['mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        $this->assertTrue($img->esImagen());
        $this->assertTrue($pdf->esPdf());
        $this->assertFalse($docx->esImagen());
        $this->assertFalse($docx->esPdf());
    }
}
