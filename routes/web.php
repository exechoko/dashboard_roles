<?php

use App\Http\Controllers\AudioTranscriptionController;
use App\Http\Controllers\BodycamController;
use App\Http\Controllers\EntregasBodycamsController;
use App\Http\Controllers\EntregasEquiposController;
use App\Http\Controllers\PasswordVaultController;
use Illuminate\Support\Facades\Route;
//agregamos los controladores
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\TipoTerminalController;
use App\Http\Controllers\DependenciaController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\FlotaGeneralController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CamaraController;
use App\Http\Controllers\Mapacontroller;
use App\Http\Controllers\TipoCamaraController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CamaraFisicaController;
use App\Http\Controllers\SitioController;
use App\Http\Controllers\CecocoController;
use App\Http\Controllers\TranscripcionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
})->name('login.view');


Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('blogs', BlogController::class);
    Route::resource('equipos', EquipoController::class);
    Route::resource('terminales', TipoTerminalController::class);
    Route::resource('bodycams', BodycamController::class);

    Route::post('/profile/update', [UsuarioController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/update-theme', [UsuarioController::class, 'updateTheme'])->name('profile.updateTheme')->middleware('auth');

    Route::get('dependencias/crear-general', [DependenciaController::class, 'createGeneral'])->name('dependencias.crear-general');
    Route::post('dependencias/store-general', [DependenciaController::class, 'storeGeneral'])->name('dependencias.store-general');
    Route::get('get-posibles-padres', [DependenciaController::class, 'getPosiblesPadres'])->name('getPosiblesPadres');
    Route::get('get-dependencias-por-tipo', [DependenciaController::class, 'getDependenciasPorTipo'])->name('getDependenciasPorTipo');
    Route::get('get-departamentales', [App\Http\Controllers\DependenciaController::class, 'getDepartamentales'])->name('getDepartamentales');
    Route::get('get-divisiones', [App\Http\Controllers\DependenciaController::class, 'getDivisiones'])->name('getDivisiones');
    Route::get('get-comisarias', [App\Http\Controllers\DependenciaController::class, 'getComisarias'])->name('getComisarias');
    Route::resource('dependencias', DependenciaController::class);
    Route::resource('vehiculos', VehiculoController::class);
    Route::resource('recursos', RecursoController::class);
    Route::resource('flota', FlotaGeneralController::class);
    Route::resource('camaras', CamaraController::class);
    Route::resource('camaras_fisicas', CamaraFisicaController::class);
    Route::resource('mapa', Mapacontroller::class);
    Route::resource('tipo-camara', TipoCamaraController::class);
    Route::resource('auditoria', AuditoriaController::class);
    Route::resource('sitios', SitioController::class);

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::post('dependencias/store-general', [DependenciaController::class, 'storeGeneral'])->name('dependencias.store-general');
    Route::get('get-posibles-padres', [DependenciaController::class, 'getPosiblesPadres'])->name('getPosiblesPadres');
    Route::get('get-dependencias-por-tipo', [DependenciaController::class, 'getDependenciasPorTipo'])->name('getDependenciasPorTipo');
    Route::get('get-departamentales', [App\Http\Controllers\DependenciaController::class, 'getDepartamentales'])->name('getDepartamentales');
    Route::get('get-divisiones', [App\Http\Controllers\DependenciaController::class, 'getDivisiones'])->name('getDivisiones');
    Route::get('get-comisarias', [App\Http\Controllers\DependenciaController::class, 'getComisarias'])->name('getComisarias');

    Route::get('/generate-docx/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'generateDocx'])->name('generateDocx');
    Route::get('/generate-docx/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTemplate'])->name('generateDocxConTemplate');
    Route::get('/generate-docx/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTabla'])->name('generateDocxConTabla');
    Route::get('/ver-historico/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'verHistorico'])->name('verHistorico');
    Route::get('/flota/historico/{id}/imprimir', [FlotaGeneralController::class, 'imprimirHistorico'])->name('flota.historico.imprimir');
    Route::get('/ver-historico-desde-equipo/{id}', [App\Http\Controllers\EquipoController::class, 'verHistoricoDesdeEquipo'])->name('verHistoricoDesdeEquipo');
    Route::get('/busqueda-avanzada', [App\Http\Controllers\FlotaGeneralController::class, 'busquedaAvanzada'])->name('flota.busquedaAvanzada');
    Route::post('/update-historico/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'update_historico'])->name('flota.update_historico');
    Route::get('get-recursos', [App\Http\Controllers\FlotaGeneralController::class, 'getRecursosJSON'])->name('getRecursosJSON');

    Route::post('/get-equipos-sin-funcionar-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposSinFuncionarJSON'])->name('get-equipos-sin-funcionar-json');
    Route::post('/get-equipos-baja-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposBajaJSON'])->name('get-equipos-baja-json');
    Route::post('/get-equipos-en-revision-json', [App\Http\Controllers\DashboardController::class,'getEquiposEnRevisionJSON'])->name('get-equipos-en-revision-json');
    Route::post('/get-equipos-funcionales-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposFuncionalesJSON'])->name('get-equipos-funcionales-json');
    Route::post('/get-equipos-provistos-por-pg-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposProvistosPorPGJSON'])->name('get-equipos-provistos-por-pg-json');
    Route::post('/get-equipos-provistos-por-telecom-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposProvistosPorTELECOMJSON'])->name('get-equipos-provistos-por-telecom-json');
    Route::post('/get-equipos-provistos-por-per-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposProvistosPorPERJSON'])->name('get-equipos-provistos-por-per-json');
    Route::post('/get-moviles-json', [App\Http\Controllers\DashboardController::class, 'getMovilesJSON'])->name('get-moviles-json');
    Route::post('/get-motos-json', [App\Http\Controllers\DashboardController::class, 'getMotosJSON'])->name('get-motos-json');
    Route::post('/get-desinstalaciones-parciales-json', [App\Http\Controllers\DashboardController::class, 'getDesinstalacionesParcialesJSON'])->name('get-desinstalaciones-parciales-json');
    Route::post('/get-equipos-PG-json', [App\Http\Controllers\DashboardController::class, 'getEquiposPgJSON'])->name('get-equipos-PG-json');
    Route::post('/get-equipos-stock-json', [App\Http\Controllers\DashboardController::class, 'getEquiposEnStockJSON'])->name('get-equipos-stock-json');
    Route::post('/get-equipos-departamental-json', [App\Http\Controllers\DashboardController::class, 'getEquiposPorDepartamentalJSON'])->name('get-equipos-departamental-json');
    Route::post('/get-equipos-division-911-json', [App\Http\Controllers\DashboardController::class, 'getEquiposDivision911JSON'])->name('get-equipos-division-911-json');
    Route::post('/get-equipos-division-bancaria-json', [App\Http\Controllers\DashboardController::class, 'getEquiposDivisionBancariaJSON'])->name('get-equipos-division-bancaria-json');

    //Route::get('/showmap', [App\Http\Controllers\MapaController::class, 'showMap'])->name('mapa.showMap');
    Route::post('/import-camaras', [App\Http\Controllers\CamaraController::class, 'importExcel'])->name('camaras.import');
    Route::get('/export-camaras', [App\Http\Controllers\CamaraController::class, 'exportExcel'])->name('camaras.export');
    Route::get('/export-sitios', [App\Http\Controllers\SitioController::class, 'exportExcel'])->name('sitios.export');
    Route::get('/export-equipos', [App\Http\Controllers\EquipoController::class, 'exportExcel'])->name('equipos.export');
    Route::post('/import-camaras-fisicas', [App\Http\Controllers\CamaraFisicaController::class, 'importExcel'])->name('camaras-fisicas.import');
    Route::get('/export-camaras-fisicas', [App\Http\Controllers\CamaraFisicaController::class, 'exportExcel'])->name('camaras-fisicas.export');

    Route::get('/indexMoviles', [App\Http\Controllers\CecocoController::class, 'indexMoviles'])->name('indexMoviles');
    Route::get('/indexLlamadas', [App\Http\Controllers\CecocoController::class, 'indexLlamadas'])->name('indexLlamadas');
    Route::post('/get-llamadas', [App\Http\Controllers\CecocoController::class, 'getLlamadas'])->name('get-llamadas');
    Route::post('/get-moviles', [App\Http\Controllers\CecocoController::class, 'getRecorridosMoviles'])->name('get-moviles');
    Route::post('/get-moviles-parados', [App\Http\Controllers\CecocoController::class, 'obtenerIntervalosParado'])->name('get-moviles-parados');
    Route::get('/get-eventos', [App\Http\Controllers\CecocoController::class, 'getEventos'])->name('get-eventos');
    Route::get('/indexMapaCalor', [App\Http\Controllers\CecocoController::class, 'indexMapaCalor'])->name('indexMapaCalor');
    Route::get('/indexMapaCecocoEnVivo', [App\Http\Controllers\CecocoController::class, 'indexMapaCecocoEnVivo'])->name('indexMapaCecocoEnVivo');
    Route::get('/getServiciosCecoco', [App\Http\Controllers\CecocoController::class, 'getServicios'])->name('getServiciosCecoco');
    Route::get('/getRecursosCecoco', [App\Http\Controllers\CecocoController::class, 'getRecursosCecoco'])->name('getRecursosCecoco');

    Route::get('/exportarCamaras', [App\Http\Controllers\MapaController::class, 'exportarExcel'])->name('mapa.exportar');
    Route::post('/camaras/{id}/reiniciar', [CamaraController::class, 'reiniciar'])->name('camaras.reiniciar');

    Route::get('/transcribir', [TranscripcionController::class, 'index'])->name('transcribe.index');
    Route::post('/transcribir', [TranscripcionController::class, 'transcribe'])->name('transcribe.audio');

    Route::get('/transcription', [AudioTranscriptionController::class, 'index'])->name('transcription.index');
    Route::post('/generate-upload-url', [AudioTranscriptionController::class, 'generateUploadUrl']);
    Route::post('/upload-file', [AudioTranscriptionController::class, 'uploadFile']);
    Route::get('/get-results', [AudioTranscriptionController::class, 'getResults']);
    Route::get('/get-results-by-filename', [AudioTranscriptionController::class, 'getResultsByFileName']);
    Route::get('/get-historial', [AudioTranscriptionController::class, 'getHistorial'])->name('getHistorial');

    // Rutas principales del CRUD
    Route::resource('entrega-equipos', EntregasEquiposController::class)->names([
        'index' => 'entrega-equipos.index',
        'create' => 'entrega-equipos.create',
        'store' => 'entrega-equipos.store',
        'show' => 'entrega-equipos.show',
        'edit' => 'entrega-equipos.edit',
        'update' => 'entrega-equipos.update',
        'destroy' => 'entrega-equipos.destroy'
    ]);
    // Rutas adicionales específicas para entregas de equipos
    Route::prefix('entrega-equipos')->name('entrega-equipos.')->group(function () {
        // Generar documento Word
        Route::get('{id}/documento', [EntregasEquiposController::class, 'generarDocumento'])
            ->name('documento')
            ->middleware('can:crear-entrega-equipos');
        // Devolver equipos (cambiar estado a devuelto)
        /*Route::patch('{id}/devolver', [EntregasEquiposController::class, 'devolver'])
            ->name('devolver')
            ->middleware('can:crear-entrega-equipos');*/
        // Reportar equipos como perdidos
        Route::patch('{id}/reportar-perdido', [EntregasEquiposController::class, 'reportarPerdido'])
            ->name('reportar-perdido')
            ->middleware('can:crear-entrega-equipos');
        // Exportar listado a Excel/PDF
        Route::get('exportar/{formato}', [EntregasEquiposController::class, 'exportar'])
            ->name('exportar')
            ->where('formato', 'excel|pdf')
            ->middleware('can:crear-entrega-equipos');
        // Buscar equipos disponibles via AJAX
        Route::get('buscar-equipos', [EntregasEquiposController::class, 'buscarEquipos'])
            ->name('buscar-equipos')
            ->middleware('can:crear-entrega-equipos');
        // Duplicar una entrega existente
        Route::post('{id}/duplicar', [EntregasEquiposController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:crear-entrega-equipos');
    });
    // Dashboard de entregas
    Route::get('entregas/dashboard', [EntregasEquiposController::class, 'dashboard'])
        ->name('entregas.dashboard')
        ->middleware('can:ver-menu-entregas');
    // Reportes de entregas
    Route::prefix('entregas/reportes')->name('entregas.reportes.')->group(function () {
        Route::get('/', [EntregasEquiposController::class, 'reportesIndex'])
            ->name('index')
            ->middleware('can:ver-reportes-entregas');
        Route::get('equipos-entregados', [EntregasEquiposController::class, 'reporteEquiposEntregados'])
            ->name('equipos-entregados')
            ->middleware('can:ver-reportes-entregas');
        Route::get('por-dependencia', [EntregasEquiposController::class, 'reportePorDependencia'])
            ->name('por-dependencia')
            ->middleware('can:ver-reportes-entregas');
    });
    Route::get('entrega-equipos/{id}/devolver', [EntregasEquiposController::class, 'devolver'])
        ->name('entrega-equipos.devolver');

    Route::post('entrega-equipos/{id}/procesar-devolucion', [EntregasEquiposController::class, 'procesarDevolucion'])
        ->name('entrega-equipos.procesar-devolucion');

    Route::get('entrega-equipos/{entregaId}/devolucion/{devolucionId}', [EntregasEquiposController::class, 'mostrarDevolucion'])
        ->name('entrega-equipos.devolucion.detalle');

    Route::delete('entrega-equipos/{entregaId}/devolucion/{devolucionId}', [EntregasEquiposController::class, 'eliminarDevolucion'])
        ->name('entrega-equipos.devolucion.eliminar');

    // Ruta para generar documento de entrega
    Route::get('entrega-equipos/{id}/documento', [EntregasEquiposController::class, 'generarDocumento'])
        ->name('entrega-equipos.documento');
    // Ruta para descargar el archivo generado
    Route::get('entrega-equipos/{id}/descargar', [EntregasEquiposController::class, 'descargarArchivo'])
        ->name('entrega-equipos.descargar');
    // Ruta para previsualizar el documento generado
    Route::get('entrega-equipos/previsualizar/{id}', [EntregasEquiposController::class, 'previsualizar'])
        ->name('entrega-equipos.previsualizar');

    // Rutas principales del CRUD para entregas de bodycams
    Route::resource('entrega-bodycams', EntregasBodycamsController::class)->names([
        'index' => 'entrega-bodycams.index',
        'create' => 'entrega-bodycams.create',
        'store' => 'entrega-bodycams.store',
        'show' => 'entrega-bodycams.show',
        'edit' => 'entrega-bodycams.edit',
        'update' => 'entrega-bodycams.update',
        'destroy' => 'entrega-bodycams.destroy'
    ]);

    // Rutas adicionales específicas para entregas de bodycams
    Route::prefix('entrega-bodycams')->name('entrega-bodycams.')->group(function () {
        // Generar documento Word
        Route::get('{id}/documento', [EntregasBodycamsController::class, 'generarDocumento'])
            ->name('documento')
            ->middleware('can:crear-entrega-bodycams');

        // Reportar bodycams como perdidas
        Route::patch('{id}/reportar-perdido', [EntregasBodycamsController::class, 'reportarPerdido'])
            ->name('reportar-perdido')
            ->middleware('can:crear-entrega-bodycams');

        // Exportar listado a Excel/PDF
        Route::get('exportar/{formato}', [EntregasBodycamsController::class, 'exportar'])
            ->name('exportar')
            ->where('formato', 'excel|pdf')
            ->middleware('can:crear-entrega-bodycams');

        // Buscar bodycams disponibles via AJAX
        Route::get('buscar-bodycams', [EntregasBodycamsController::class, 'buscarBodycams'])
            ->name('buscar-bodycams')
            ->middleware('can:crear-entrega-bodycams');

        // Duplicar una entrega existente
        Route::post('{id}/duplicar', [EntregasBodycamsController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:crear-entrega-bodycams');
    });

    // Dashboard de entregas de bodycams
    Route::get('entregas-bodycams/dashboard', [EntregasBodycamsController::class, 'dashboard'])
        ->name('entregas-bodycams.dashboard')
        ->middleware('can:ver-menu-entregas-bodycams');

    // Reportes de entregas de bodycams
    Route::prefix('entregas-bodycams/reportes')->name('entregas-bodycams.reportes.')->group(function () {
        Route::get('/', [EntregasBodycamsController::class, 'reportesIndex'])
            ->name('index')
            ->middleware('can:ver-reportes-entregas-bodycams');

        Route::get('bodycams-entregadas', [EntregasBodycamsController::class, 'reporteBodycamsEntregadas'])
            ->name('bodycams-entregadas')
            ->middleware('can:ver-reportes-entregas-bodycams');

        Route::get('por-dependencia', [EntregasBodycamsController::class, 'reportePorDependencia'])
            ->name('por-dependencia')
            ->middleware('can:ver-reportes-entregas-bodycams');
    });

    // Rutas para devolución de bodycams
    Route::get('entrega-bodycams/{id}/devolver', [EntregasBodycamsController::class, 'devolver'])
        ->name('entrega-bodycams.devolver');

    Route::post('entrega-bodycams/{id}/procesar-devolucion', [EntregasBodycamsController::class, 'procesarDevolucion'])
        ->name('entrega-bodycams.procesar-devolucion');

    Route::get('entrega-bodycams/{entregaId}/devolucion/{devolucionId}', [EntregasBodycamsController::class, 'mostrarDevolucion'])
        ->name('entrega-bodycams.devolucion.detalle');

    Route::delete('entrega-bodycams/{entregaId}/devolucion/{devolucionId}', [EntregasBodycamsController::class, 'eliminarDevolucion'])
        ->name('entrega-bodycams.devolucion.eliminar');

    // Ruta para descargar el archivo generado
    Route::get('entrega-bodycams/{id}/descargar', [EntregasBodycamsController::class, 'descargarArchivo'])
        ->name('entrega-bodycams.descargar');

    // Ruta para previsualizar el documento generado
    Route::get('entrega-bodycams/previsualizar/{id}', [EntregasBodycamsController::class, 'previsualizar'])
        ->name('entrega-bodycams.previsualizar');

    // Rutas del gestor de contraseñas
    Route::resource('password-vault', PasswordVaultController::class);
    Route::get('password-vault-generate', [PasswordVaultController::class, 'generatePassword'])
        ->name('password-vault.generate');
    Route::post('password-vault/{passwordVault}/toggle-favorite', [PasswordVaultController::class, 'toggleFavorite'])
        ->name('password-vault.toggle-favorite');
    Route::get('password-vault/{passwordVault}/get-password', [PasswordVaultController::class, 'getPassword'])
        ->name('password-vault.get-password');
    Route::post('password-vault/{passwordVault}/share', [PasswordVaultController::class, 'share'])
        ->name('password-vault.share')
        ->middleware('can:owner,passwordVault'); // Restringe a que solo el dueño pueda compartir
    Route::get('password-vault/{passwordVault}/shares', [PasswordVaultController::class, 'getShares'])
        ->name('password-vault.get-shares')
        ->middleware('can:owner,passwordVault'); // Restringe a que solo el dueño pueda ver la lista
    Route::delete('password-shares/{share}/revoke', [PasswordVaultController::class, 'revokeShare'])
        ->name('password-vault.revoke-share'); // Revoca el acceso

    //Optimizar sistema
    Route::get('optimizar', function () {
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        Auth::logout();

        return redirect()->route('login.view')
            ->with('status', '✅ Optimización completada correctamente');
    });
});
