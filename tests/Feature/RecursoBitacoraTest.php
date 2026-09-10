<?php

namespace Tests\Feature;

use App\Models\Recurso;
use App\Models\RecursoBitacora;
use App\Models\RecursoBitacoraSolicitud;
use App\Models\RecursoBitacoraVista;
use App\Models\RecursoEstadoSeccion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecursoBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string ...$permisos): User
    {
        $user = User::factory()->create();
        foreach ($permisos as $p) {
            $user->givePermissionTo($p);
        }

        return $user;
    }

    private function recurso(): Recurso
    {
        return Recurso::query()->whereNotNull('vehiculo_id')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function payload(array $over = []): array
    {
        return array_merge([
            'fecha_hora'  => '2099-08-01T09:30',
            'categoria'   => 'mantenimiento',
            'descripcion' => 'Ruido en el tren delantero.',
        ], $over);
    }

    public function test_el_operador_registra_una_entrada(): void
    {
        $recurso = $this->recurso();

        $this->actingAs($this->usuario('gestionar-flota-911'))
            ->post(route('flota-911.estado-flota.bitacora.store', $recurso->id), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('recurso_bitacora', [
            'recurso_id'  => $recurso->id,
            'categoria'   => 'mantenimiento',
            'descripcion' => 'Ruido en el tren delantero.',
        ]);
    }

    public function test_sin_permiso_de_gestion_no_puede_cargar(): void
    {
        $this->actingAs($this->usuario('ver-flota-911'))
            ->post(route('flota-911.estado-flota.bitacora.store', $this->recurso()->id), $this->payload())
            ->assertForbidden();
    }

    public function test_entrada_abierta_con_poner_en_taller_cambia_el_estado_del_recurso(): void
    {
        $recurso = $this->recurso();
        RecursoEstadoSeccion::where('recurso_id', $recurso->id)->delete();

        $this->actingAs($this->usuario('gestionar-flota-911'))
            ->post(route('flota-911.estado-flota.bitacora.store', $recurso->id), $this->payload([
                'estado'          => 'abierto',
                'poner_en_taller' => '1',
            ]))
            ->assertRedirect();

        $this->assertSame('en_taller', RecursoEstadoSeccion::where('recurso_id', $recurso->id)->value('estado'));
    }

    public function test_cerrar_una_entrada_registra_la_devolucion(): void
    {
        $recurso = $this->recurso();
        $user = $this->usuario('gestionar-flota-911');
        RecursoEstadoSeccion::updateOrCreate(['recurso_id' => $recurso->id], ['estado' => 'en_taller', 'user_id' => $user->id]);

        $entrada = RecursoBitacora::factory()->abierta()->create(['recurso_id' => $recurso->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->patch(route('flota-911.bitacora.cerrar', $entrada->id), [
                'fecha_cierre'       => '2099-08-10T15:00',
                'nota_cierre'        => 'Se cambió el rodamiento.',
                'volver_en_servicio' => '1',
            ])
            ->assertRedirect();

        $entrada->refresh();
        $this->assertSame('cerrado', $entrada->estado);
        $this->assertNotNull($entrada->cerrada_en);
        $this->assertSame($user->id, $entrada->cerrada_por);
        $this->assertTrue($entrada->seguimientos()->where('descripcion', 'like', '%rodamiento%')->exists());
        $this->assertSame('en_servicio', RecursoEstadoSeccion::where('recurso_id', $recurso->id)->value('estado'));
    }

    public function test_ver_la_bitacora_marca_visto_para_el_usuario(): void
    {
        $recurso = $this->recurso();
        $user = $this->usuario('ver-flota-911');

        $this->actingAs($user)->get(route('flota-911.estado-flota.bitacora', $recurso->id))->assertOk();

        $this->assertDatabaseHas('recurso_bitacora_vistas', [
            'recurso_id' => $recurso->id,
            'user_id'    => $user->id,
        ]);
    }

    public function test_seguimiento_libre_con_adjunto(): void
    {
        Storage::fake('public');
        $recurso = $this->recurso();
        $user = $this->usuario('gestionar-flota-911');
        $entrada = RecursoBitacora::factory()->create(['recurso_id' => $recurso->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('flota-911.bitacora.seguimientos.store', $entrada->id), [
                'descripcion' => 'Adjunto la factura del taller.',
                'adjuntos'    => [UploadedFile::fake()->create('factura.pdf', 200, 'application/pdf')],
            ])
            ->assertRedirect();

        $seguimiento = $entrada->seguimientos()->firstOrFail();
        $this->assertSame('Adjunto la factura del taller.', $seguimiento->descripcion);
        $this->assertCount(1, $seguimiento->adjuntos);
    }

    public function test_solicitud_de_edicion_no_toca_la_entrada_hasta_que_un_moderador_aprueba(): void
    {
        $recurso = $this->recurso();
        $operador = $this->usuario('gestionar-flota-911');
        $moderador = $this->usuario('moderar-bitacora-flota-911');
        $entrada = RecursoBitacora::factory()->create([
            'recurso_id' => $recurso->id, 'user_id' => $operador->id, 'descripcion' => 'Texto original',
        ]);

        $this->actingAs($operador)
            ->post(route('flota-911.bitacora.solicitudes.store', $entrada->id), [
                'tipo'    => 'edicion',
                'cambios' => ['descripcion' => 'Texto corregido'],
                'motivo'  => 'Me equivoqué',
            ])
            ->assertRedirect();

        $this->assertSame('Texto original', $entrada->fresh()->descripcion);

        $solicitud = RecursoBitacoraSolicitud::where('bitacora_id', $entrada->id)->firstOrFail();
        $this->assertSame(RecursoBitacoraSolicitud::ESTADO_PENDIENTE, $solicitud->estado);

        $this->actingAs($moderador)
            ->patch(route('flota-911.bitacora.solicitudes.aprobar', $solicitud->id))
            ->assertRedirect();

        $this->assertSame('Texto corregido', $entrada->fresh()->descripcion);
        $this->assertSame(RecursoBitacoraSolicitud::ESTADO_APROBADA, $solicitud->fresh()->estado);
    }

    public function test_solicitud_de_eliminacion_aprobada_borra_la_entrada(): void
    {
        $recurso = $this->recurso();
        $operador = $this->usuario('gestionar-flota-911');
        $moderador = $this->usuario('moderar-bitacora-flota-911');
        $entrada = RecursoBitacora::factory()->create(['recurso_id' => $recurso->id, 'user_id' => $operador->id]);

        $this->actingAs($operador)->post(route('flota-911.bitacora.solicitudes.store', $entrada->id), [
            'tipo' => 'eliminacion', 'motivo' => 'Duplicada',
        ])->assertRedirect();

        $solicitud = RecursoBitacoraSolicitud::where('bitacora_id', $entrada->id)->firstOrFail();
        $this->actingAs($moderador)->patch(route('flota-911.bitacora.solicitudes.aprobar', $solicitud->id))->assertRedirect();

        $this->assertDatabaseMissing('recurso_bitacora', ['id' => $entrada->id]);
    }

    public function test_rechazar_solicitud_exige_motivo_y_no_aplica_cambios(): void
    {
        $recurso = $this->recurso();
        $operador = $this->usuario('gestionar-flota-911');
        $moderador = $this->usuario('moderar-bitacora-flota-911');
        $entrada = RecursoBitacora::factory()->create([
            'recurso_id' => $recurso->id, 'user_id' => $operador->id, 'descripcion' => 'Original',
        ]);
        $this->actingAs($operador)->post(route('flota-911.bitacora.solicitudes.store', $entrada->id), [
            'tipo' => 'edicion', 'cambios' => ['descripcion' => 'Otra cosa'],
        ]);
        $solicitud = RecursoBitacoraSolicitud::where('bitacora_id', $entrada->id)->firstOrFail();

        $this->actingAs($moderador)
            ->patch(route('flota-911.bitacora.solicitudes.rechazar', $solicitud->id), [])
            ->assertSessionHasErrors('motivo_resolucion');

        $this->actingAs($moderador)
            ->patch(route('flota-911.bitacora.solicitudes.rechazar', $solicitud->id), ['motivo_resolucion' => 'No corresponde'])
            ->assertRedirect();

        $this->assertSame('Original', $entrada->fresh()->descripcion);
        $this->assertSame(RecursoBitacoraSolicitud::ESTADO_RECHAZADA, $solicitud->fresh()->estado);
    }

    public function test_solo_un_moderador_ve_las_solicitudes(): void
    {
        $this->actingAs($this->usuario('gestionar-flota-911'))
            ->get(route('flota-911.bitacora.solicitudes.index'))
            ->assertForbidden();

        $this->actingAs($this->usuario('moderar-bitacora-flota-911'))
            ->get(route('flota-911.bitacora.solicitudes.index'))
            ->assertOk();
    }

    public function test_estado_flota_ahora_lo_ve_quien_tiene_ver_flota_911(): void
    {
        $this->actingAs($this->usuario('ver-flota-911'))
            ->get(route('flota-911.informes.estado-flota'))
            ->assertOk();
    }

    public function test_el_listado_de_estado_flota_busca_y_muestra_la_ultima_entrada(): void
    {
        $recurso = $this->recurso();
        RecursoBitacora::factory()->create([
            'recurso_id' => $recurso->id,
            'user_id'    => $this->usuario('gestionar-flota-911')->id,
            'fecha_hora' => now()->subDay(),
            'categoria'  => 'gomeria',
            'descripcion' => 'Se pinchó la rueda trasera izquierda.',
        ]);

        $this->actingAs($this->usuario('ver-flota-911'))
            ->get(route('flota-911.informes.estado-flota', ['q' => $recurso->nombre]))
            ->assertOk()
            ->assertSee($recurso->nombre)
            ->assertSee('Se pinchó la rueda');

        $this->actingAs($this->usuario('ver-flota-911'))
            ->get(route('flota-911.informes.estado-flota', ['q' => 'zxqw-no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_los_recursos_con_novedades_nuevas_van_primero(): void
    {
        [$conNuevas, $sinNuevas] = Recurso::query()->whereNotNull('vehiculo_id')->take(2)->get()->all();
        $autor = $this->usuario('gestionar-flota-911');
        $lector = $this->usuario('ver-flota-911');

        // "sinNuevas": el lector ya lo vio después de la entrada
        RecursoBitacora::factory()->create(['recurso_id' => $sinNuevas->id, 'user_id' => $autor->id, 'fecha_hora' => now()->subDays(3)]);
        RecursoBitacoraVista::create(['recurso_id' => $sinNuevas->id, 'user_id' => $lector->id, 'visto_en' => now()]);

        // "conNuevas": entrada reciente, nunca visto
        RecursoBitacora::factory()->create(['recurso_id' => $conNuevas->id, 'user_id' => $autor->id, 'fecha_hora' => now()->subHour()]);

        $html = $this->actingAs($lector)->get(route('flota-911.informes.estado-flota'))->assertOk()->getContent();

        $posConNuevas = strpos($html, $conNuevas->nombre);
        $posSinNuevas = strpos($html, $sinNuevas->nombre);
        $this->assertNotFalse($posConNuevas);
        $this->assertNotFalse($posSinNuevas);
        $this->assertLessThan($posSinNuevas, $posConNuevas, 'El recurso con novedades nuevas debe listarse antes.');
    }
}
