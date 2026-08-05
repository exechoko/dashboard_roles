<?php

namespace Tests\Feature;

use App\Models\ActivacionTotem;
use App\Models\Camara;
use App\Models\EventoCecoco;
use App\Models\User;
use App\Services\ArchivoHashService;
use App\Services\DetectorActivacionesTotem;
use App\Services\SubidaVideoTotemService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ActivacionTotemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->get(route('activaciones-totem.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_el_listado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Espejo y Crausaz',
        ]);
        ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('activaciones-totem.index'));

        $response->assertOk()
            ->assertViewIs('activaciones-totem.index')
            ->assertSee('Activaciones Tótem')
            ->assertSee($evento->nro_expediente);
    }

    public function test_estado_subidas_devuelve_el_subida_estado_de_los_ids_pedidos(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create();
        $procesando = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'subida_estado' => ActivacionTotem::SUBIDA_PROCESANDO,
        ]);
        $completado = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999950',
            'fecha_evento' => now(),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'subida_estado' => ActivacionTotem::SUBIDA_COMPLETADO,
        ]);

        $response = $this->actingAs($admin)->getJson(
            route('activaciones-totem.estado-subidas', ['ids' => "{$procesando->id},{$completado->id}"])
        );

        $response->assertOk()->assertJson([
            (string) $procesando->id => ActivacionTotem::SUBIDA_PROCESANDO,
            (string) $completado->id => ActivacionTotem::SUBIDA_COMPLETADO,
        ]);
    }

    public function test_el_dashboard_muestra_la_tarjeta_de_activaciones_pendientes(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Racedo y Blvd',
        ]);
        ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk()->assertSee('Activaciones Tótem pendientes');
    }

    public function test_esta_vencida_cuando_pasaron_mas_de_6_meses_y_sigue_pendiente_o_descargada(): void
    {
        $vieja = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999901',
            'fecha_evento' => now()->subMonths(7),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $reciente = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999902',
            'fecha_evento' => now()->subMonths(2),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
        ]);

        $this->assertTrue($vieja->esVencida());
        $this->assertFalse($reciente->esVencida());
    }

    public function test_no_esta_vencida_si_esta_descartada_o_eliminada_aunque_sea_vieja(): void
    {
        $descartada = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999903',
            'fecha_evento' => now()->subMonths(8),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARTADO,
        ]);

        $eliminada = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999904',
            'fecha_evento' => now()->subMonths(8),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_ELIMINADO,
        ]);

        $this->assertFalse($descartada->esVencida());
        $this->assertFalse($eliminada->esVencida());
    }

    public function test_scope_vencidas_solo_trae_pendientes_y_descargadas_con_mas_de_6_meses(): void
    {
        ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999905',
            'fecha_evento' => now()->subMonths(7),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
        ]);
        ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999906',
            'fecha_evento' => now()->subMonths(1),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $this->assertSame(1, ActivacionTotem::vencidas()->whereIn('nro_expediente', ['9999905', '9999906'])->count());
    }

    public function test_marcar_como_eliminado_registra_usuario_y_fecha(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => EventoCecoco::factory()->create()->id,
            'nro_expediente' => '9999907',
            'fecha_evento' => now()->subMonths(7),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('activaciones-totem.eliminar', $activacion));

        $response->assertRedirect(route('activaciones-totem.index'));
        $this->assertDatabaseHas('activaciones_totem', [
            'id' => $activacion->id,
            'estado' => ActivacionTotem::ESTADO_ELIMINADO,
            'eliminado_por' => $admin->id,
        ]);
        $this->assertNotNull($activacion->fresh()->fecha_eliminado);
        $this->assertFalse($activacion->fresh()->esVencida());
    }

    public function test_el_listado_muestra_marcar_como_eliminado_para_un_descargado_reciente(): void
    {
        // Regresión: el botón "Marcar como eliminado" estaba oculto salvo que
        // el registro estuviera vencido (+6 meses), sin forma de resetear un
        // registro recién descargado por error (tótem equivocado, reintento).
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => now()->subDay(),
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now(),
        ]);

        $this->assertFalse($activacion->esVencida());

        $response = $this->actingAs($admin)->get(route('activaciones-totem.index'));

        $response->assertOk()->assertSee(route('activaciones-totem.eliminar', $activacion), false);
    }

    public function test_detecta_un_evento_con_totem_en_la_descripcion(): void
    {
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Se recepciona la activacion del Totem calle Celia Torra, femenina solicita ambulancia',
        ]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(1, $creadas);
        $this->assertDatabaseHas('activaciones_totem', [
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);
    }

    public function test_detecta_bde_como_palabra_completa(): void
    {
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Se activa el BDE ubicado en Artigas y Cochrane, masculino solicita ambulancia',
        ]);

        app(DetectorActivacionesTotem::class)->detectar();

        $this->assertDatabaseHas('activaciones_totem', [
            'evento_cecoco_id' => $evento->id,
            'palabra_detectada' => 'BDE',
        ]);
    }

    public function test_no_detecta_bde_pegado_dentro_de_otra_palabra(): void
    {
        EventoCecoco::factory()->create([
            'descripcion' => 'Se comunica una femenina afuerabde una vivienda en Grabde calle',
        ]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(0, $creadas);
        $this->assertDatabaseCount('activaciones_totem', 0);
    }

    public function test_no_detecta_el_boliche_totem(): void
    {
        EventoCecoco::factory()->create([
            'descripcion' => 'Se comunica un masculino solicitando personal en el boliche TOTEM',
        ]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(0, $creadas);
        $this->assertDatabaseCount('activaciones_totem', 0);
    }

    public function test_reescanear_no_duplica_ni_resucita_descartados(): void
    {
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Ituzaingo y Sanchez',
        ]);

        app(DetectorActivacionesTotem::class)->detectar();
        $activacion = ActivacionTotem::where('evento_cecoco_id', $evento->id)->firstOrFail();
        $activacion->update(['estado' => ActivacionTotem::ESTADO_DESCARTADO]);

        $creadas = app(DetectorActivacionesTotem::class)->detectar();

        $this->assertSame(0, $creadas);
        $this->assertDatabaseCount('activaciones_totem', 1);
        $this->assertDatabaseHas('activaciones_totem', [
            'evento_cecoco_id' => $evento->id,
            'estado' => ActivacionTotem::ESTADO_DESCARTADO,
        ]);
    }

    public function test_marcar_como_descargado_registra_usuario_y_fecha(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create([
            'descripcion' => 'Activacion del Totem ubicado en Racedo y America',
        ]);
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('activaciones-totem.update', $activacion), [
                'observaciones' => 'Video descargado del sistema de videovigilancia',
            ]);

        $response->assertRedirect(route('activaciones-totem.index'));
        $this->assertDatabaseHas('activaciones_totem', [
            'id' => $activacion->id,
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'observaciones' => 'Video descargado del sistema de videovigilancia',
        ]);
        $this->assertNotNull($activacion->fresh()->fecha_descarga);
    }

    private function totemDeRedTemporal(): array
    {
        $rutaBase = storage_path('app/testing-totems-red/' . uniqid('base_'));
        $carpeta = 'Totem De Prueba';
        File::ensureDirectoryExists($rutaBase . '\\' . $carpeta);

        $totem = Camara::create([
            'nombre' => 'Tótem de prueba',
            'carpeta_red' => $carpeta,
        ]);

        return [$totem, $rutaBase];
    }

    public function test_servicio_de_subida_hashea_y_copia_a_la_carpeta_de_red(): void
    {
        [$totem, $rutaBase] = $this->totemDeRedTemporal();

        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video_prueba.mp4',
            'subida_estado' => ActivacionTotem::SUBIDA_PENDIENTE,
        ]);

        $servicio = new SubidaVideoTotemService(app(ArchivoHashService::class), $rutaBase);
        $rutaTemporal = $servicio->rutaTemporal($activacion);
        File::ensureDirectoryExists(dirname($rutaTemporal));
        File::put($rutaTemporal, 'contenido de prueba del video');

        $resultado = $servicio->procesar($activacion);

        $this->assertFileExists($resultado['ruta_archivo']);
        // El servicio ya no borra el temporal: queda a cargo de quien llama,
        // recién después de confirmar que se guardó en la base.
        $this->assertFileExists($rutaTemporal);
        @unlink($rutaTemporal);
        $this->assertSame(hash_file('sha256', $resultado['ruta_archivo']), $resultado['hash_sha256']);
        // Se conserva el nombre original del video tal cual lo exporta el sistema.
        $this->assertStringEndsWith('video_prueba.mp4', $resultado['ruta_archivo']);
        $this->assertStringContainsString($totem->carpeta_red, $resultado['ruta_archivo']);
    }

    public function test_servicio_de_subida_no_duplica_si_ya_habia_copiado_el_mismo_video_antes(): void
    {
        // Regresión: si el proceso anterior copió el video pero se cortó
        // antes de guardar el resultado en la base (ej. el scheduler se
        // reinició justo en el medio), el reintento no debe duplicar el
        // archivo ni fallar — tiene que reconocer que ya está y seguir.
        [$totem, $rutaBase] = $this->totemDeRedTemporal();

        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video_interrumpido.mp4',
            'subida_estado' => ActivacionTotem::SUBIDA_PROCESANDO,
        ]);

        $servicio = new SubidaVideoTotemService(app(ArchivoHashService::class), $rutaBase);
        $rutaTemporal = $servicio->rutaTemporal($activacion);
        File::ensureDirectoryExists(dirname($rutaTemporal));
        File::put($rutaTemporal, 'contenido del video');

        // El archivo destino ya existe con el mismo contenido: simula que un
        // intento anterior sí llegó a copiarlo antes de cortarse.
        File::put($rutaBase . '\\' . $totem->carpeta_red . '\\video_interrumpido.mp4', 'contenido del video');

        $resultado = $servicio->procesar($activacion);

        $this->assertStringEndsWith('video_interrumpido.mp4', $resultado['ruta_archivo']);
        $this->assertFileExists($rutaTemporal);
        @unlink($rutaTemporal);
    }

    public function test_servicio_de_subida_desambigua_si_ya_existe_un_archivo_con_el_mismo_nombre(): void
    {
        [$totem, $rutaBase] = $this->totemDeRedTemporal();

        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video_repetido.mp4',
            'subida_estado' => ActivacionTotem::SUBIDA_PENDIENTE,
        ]);

        // Ya existe un archivo con ese nombre exacto en la carpeta destino
        // (por ejemplo, de un ciclo anterior que se marcó como eliminado).
        File::put($rutaBase . '\\' . $totem->carpeta_red . '\\video_repetido.mp4', 'video anterior');

        $servicio = new SubidaVideoTotemService(app(ArchivoHashService::class), $rutaBase);
        $rutaTemporal = $servicio->rutaTemporal($activacion);
        File::ensureDirectoryExists(dirname($rutaTemporal));
        File::put($rutaTemporal, 'video nuevo');

        $resultado = $servicio->procesar($activacion);

        $this->assertStringEndsWith('video_repetido_(2).mp4', $resultado['ruta_archivo']);
        $this->assertSame('video anterior', File::get($rutaBase . '\\' . $totem->carpeta_red . '\\video_repetido.mp4'));
        @unlink($rutaTemporal);
    }

    public function test_servicio_de_subida_falla_si_el_totem_no_tiene_carpeta_configurada(): void
    {
        $totem = Camara::create(['nombre' => 'Tótem sin carpeta']);
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video.mp4',
        ]);

        $this->expectException(\RuntimeException::class);
        (new SubidaVideoTotemService(app(ArchivoHashService::class)))->procesar($activacion);
    }

    public function test_comando_procesa_video_pendiente_y_marca_descargado(): void
    {
        [$totem, $rutaBase] = $this->totemDeRedTemporal();

        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video_comando.mp4',
            'subida_estado' => ActivacionTotem::SUBIDA_PENDIENTE,
        ]);

        $this->app->bind(SubidaVideoTotemService::class, function ($app) use ($rutaBase, $activacion) {
            $servicio = new SubidaVideoTotemService($app->make(ArchivoHashService::class), $rutaBase);
            $rutaTemporal = $servicio->rutaTemporal($activacion);
            File::ensureDirectoryExists(dirname($rutaTemporal));
            File::put($rutaTemporal, 'contenido de prueba');

            return $servicio;
        });

        $this->artisan('totem:procesar-videos-pendientes')->assertSuccessful();

        $activacion->refresh();
        $this->assertSame(ActivacionTotem::SUBIDA_COMPLETADO, $activacion->subida_estado);
        $this->assertSame(ActivacionTotem::ESTADO_DESCARGADO, $activacion->estado);
        $this->assertNotNull($activacion->hash_sha256);
        $this->assertNotNull($activacion->ruta_archivo);
        $this->assertFileDoesNotExist(storage_path('app/totem-uploads-temp/' . $activacion->id . '_video_comando.mp4'));
    }

    public function test_comando_retoma_un_video_cuya_copia_se_interrumpio_antes_de_guardar_en_base(): void
    {
        // Regresión: el proceso anterior copió el video a la carpeta de red
        // pero se cortó antes de actualizar la base (ej. se reinició el
        // servicio del scheduler en el medio) — quedó "procesando" trabado.
        [$totem, $rutaBase] = $this->totemDeRedTemporal();

        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video_trabado.mp4',
            'subida_estado' => ActivacionTotem::SUBIDA_PROCESANDO,
        ]);
        // create() siempre pisa updated_at con "ahora"; se fuerza aparte para
        // simular que quedó trabado desde hace rato.
        DB::table('activaciones_totem')->where('id', $activacion->id)->update(['updated_at' => now()->subMinutes(20)]);

        $this->app->bind(SubidaVideoTotemService::class, function ($app) use ($rutaBase, $activacion) {
            $servicio = new SubidaVideoTotemService($app->make(ArchivoHashService::class), $rutaBase);
            $rutaTemporal = $servicio->rutaTemporal($activacion);
            File::ensureDirectoryExists(dirname($rutaTemporal));
            File::put($rutaTemporal, 'contenido ya copiado antes');

            return $servicio;
        });

        // El video ya había llegado a la carpeta de red en el intento anterior.
        File::put($rutaBase . '\\' . $totem->carpeta_red . '\\video_trabado.mp4', 'contenido ya copiado antes');

        $this->artisan('totem:procesar-videos-pendientes')->assertSuccessful();

        $activacion->refresh();
        $this->assertSame(ActivacionTotem::SUBIDA_COMPLETADO, $activacion->subida_estado);
        $this->assertSame(ActivacionTotem::ESTADO_DESCARGADO, $activacion->estado);
        $this->assertStringEndsWith('video_trabado.mp4', $activacion->ruta_archivo);
        // No duplicó el archivo con un sufijo _(2).
        $this->assertFileDoesNotExist($rutaBase . '\\' . $totem->carpeta_red . '\\video_trabado_(2).mp4');
    }

    public function test_comando_deja_en_error_si_falla_el_procesamiento(): void
    {
        // Tótem sin carpeta_red configurada: el servicio real va a fallar al procesar.
        $totem = Camara::create(['nombre' => 'Tótem sin carpeta para error']);
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'camara_id' => $totem->id,
            'nombre_archivo_original' => 'video.mp4',
            'subida_estado' => ActivacionTotem::SUBIDA_PENDIENTE,
        ]);

        $this->artisan('totem:procesar-videos-pendientes')->assertSuccessful();

        $activacion->refresh();
        $this->assertSame(ActivacionTotem::SUBIDA_ERROR, $activacion->subida_estado);
        $this->assertSame(ActivacionTotem::ESTADO_PENDIENTE, $activacion->estado);
        $this->assertNotNull($activacion->subida_error);
    }

    public function test_subir_video_bloqueado_si_ya_esta_descargado(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$totem] = $this->totemDeRedTemporal();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('activaciones-totem.subir-video', $activacion), [
            'camara_id' => $totem->id,
            'video' => UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'),
        ]);

        $response->assertRedirect(route('activaciones-totem.index'));
        $this->assertNull($activacion->fresh()->subida_estado);
    }

    public function test_subir_video_bloqueado_devuelve_json_para_requests_ajax(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$totem] = $this->totemDeRedTemporal();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('activaciones-totem.subir-video', $activacion), [
            'camara_id' => $totem->id,
            'video' => UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)->assertJsonStructure(['message']);
    }

    public function test_subir_video_exitoso_devuelve_json_para_requests_ajax(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$totem] = $this->totemDeRedTemporal();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->post(route('activaciones-totem.subir-video', $activacion), [
            'camara_id' => $totem->id,
            'video' => UploadedFile::fake()->create('video_ajax.mp4', 100, 'video/mp4'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200)->assertJsonStructure(['message']);

        $rutaTemporalEsperada = storage_path('app/totem-uploads-temp/' . $activacion->id . '_video_ajax.mp4');
        @unlink($rutaTemporalEsperada);
    }

    public function test_subir_video_guarda_temporal_y_marca_pendiente(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$totem] = $this->totemDeRedTemporal();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->post(route('activaciones-totem.subir-video', $activacion), [
            'camara_id' => $totem->id,
            'video' => UploadedFile::fake()->create('video_subida.mp4', 100, 'video/mp4'),
            'observaciones' => 'Subido para test',
        ]);

        $response->assertRedirect(route('activaciones-totem.index'));
        $activacion->refresh();
        $this->assertSame($totem->id, $activacion->camara_id);
        $this->assertSame($admin->id, $activacion->descargado_por);
        $this->assertSame(ActivacionTotem::SUBIDA_PENDIENTE, $activacion->subida_estado);
        $this->assertSame('video_subida.mp4', $activacion->nombre_archivo_original);

        $rutaTemporalEsperada = storage_path('app/totem-uploads-temp/' . $activacion->id . '_video_subida.mp4');
        $this->assertFileExists($rutaTemporalEsperada);
        @unlink($rutaTemporalEsperada);
    }

    public function test_el_listado_renderiza_sin_error_mientras_el_video_esta_en_proceso(): void
    {
        // Regresión: subirVideo() setea descargado_por de inmediato pero
        // fecha_descarga recién queda seteada cuando el comando termina de
        // procesar. La vista no puede asumir que ambos van siempre juntos.
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create();
        ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            'descargado_por' => $admin->id,
            'fecha_descarga' => null,
            'subida_estado' => ActivacionTotem::SUBIDA_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('activaciones-totem.index'));

        $response->assertOk();
    }

    public function test_descargar_video_devuelve_el_archivo(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$totem, $rutaBase] = $this->totemDeRedTemporal();
        $evento = EventoCecoco::factory()->create();

        $rutaArchivo = $rutaBase . '\\' . $totem->carpeta_red . '\\video_final.mp4';
        File::put($rutaArchivo, 'contenido final del video');

        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'camara_id' => $totem->id,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now(),
            'nombre_archivo_original' => 'video_original.mp4',
            'ruta_archivo' => $rutaArchivo,
            'hash_sha256' => hash_file('sha256', $rutaArchivo),
            'subida_estado' => ActivacionTotem::SUBIDA_COMPLETADO,
        ]);

        $response = $this->actingAs($admin)->get(route('activaciones-totem.descargar-video', $activacion));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('video_original.mp4', $response->headers->get('content-disposition'));
    }

    public function test_descargar_video_redirige_con_error_si_no_hay_archivo(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('activaciones-totem.descargar-video', $activacion));

        $response->assertRedirect(route('activaciones-totem.index'));
    }

    public function test_descargar_certificado_incluye_el_hash(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$totem] = $this->totemDeRedTemporal();
        $evento = EventoCecoco::factory()->create();
        $activacion = ActivacionTotem::create([
            'evento_cecoco_id' => $evento->id,
            'nro_expediente' => $evento->nro_expediente,
            'fecha_evento' => $evento->fecha_hora,
            'palabra_detectada' => 'totem',
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'camara_id' => $totem->id,
            'descargado_por' => $admin->id,
            'fecha_descarga' => now(),
            'nombre_archivo_original' => 'video_original.mp4',
            'ruta_archivo' => 'C:\\ruta\\ficticia\\video.mp4',
            'hash_sha256' => str_repeat('a', 64),
            'subida_estado' => ActivacionTotem::SUBIDA_COMPLETADO,
        ]);

        $response = $this->actingAs($admin)->get(route('activaciones-totem.descargar-certificado', $activacion));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $response->assertSee($activacion->nro_expediente);
        $response->assertSee(str_repeat('a', 64));
        $this->assertStringContainsString('certificado.txt', $response->headers->get('content-disposition'));
    }
}
