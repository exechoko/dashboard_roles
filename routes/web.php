<?php

use App\Http\Controllers\AudioTranscriptionController;
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
    //return view('welcome');
});


Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('blogs', BlogController::class);
    Route::resource('equipos', EquipoController::class);
    Route::resource('terminales', TipoTerminalController::class);

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

    Route::get('dependencias/crear-general', [DependenciaController::class, 'createGeneral'])->name('dependencias.crear-general');
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
    Route::post('/get-equipos-funcionales-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposFuncionalesJSON'])->name('get-equipos-funcionales-json');
    Route::post('/get-equipos-provistos-por-pg-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposProvistosPorPGJSON'])->name('get-equipos-provistos-por-pg-json');
    Route::post('/get-equipos-provistos-por-telecom-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposProvistosPorTELECOMJSON'])->name('get-equipos-provistos-por-telecom-json');
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


});
