<?php

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
use App\Http\Controllers\SitioController;

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

Route::group(['middleware' => ['auth']], function(){
    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('blogs', BlogController::class);
    Route::resource('equipos', EquipoController::class);
    Route::resource('terminales', TipoTerminalController::class);
    Route::resource('dependencias', DependenciaController::class);
    Route::resource('vehiculos', VehiculoController::class);
    Route::resource('recursos', RecursoController::class);
    Route::resource('flota', FlotaGeneralController::class);
    Route::resource('camaras', CamaraController::class);
    Route::resource('mapa', Mapacontroller::class);
    Route::resource('tipo-camara', TipoCamaraController::class);
    Route::resource('auditoria', AuditoriaController::class);
    Route::resource('sitios', SitioController::class);

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('get-departamentales', [App\Http\Controllers\DependenciaController::class, 'getDepartamentales'])->name('getDepartamentales');
    Route::get('get-divisiones', [App\Http\Controllers\DependenciaController::class, 'getDivisiones'])->name('getDivisiones');
    Route::get('get-comisarias', [App\Http\Controllers\DependenciaController::class, 'getComisarias'])->name('getComisarias');

    Route::get('/generate-docx/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'generateDocx'])->name('generateDocx');
    Route::get('/generate-docx/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTemplate'])->name('generateDocxConTemplate');
    Route::get('/generate-docx/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTabla'])->name('generateDocxConTabla');
    Route::get('/ver-historico/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'verHistorico'])->name('verHistorico');
    Route::get('/ver-historico-desde-equipo/{id}',[App\Http\Controllers\EquipoController::class, 'verHistoricoDesdeEquipo'])->name('verHistoricoDesdeEquipo');
    Route::post('/update-historico/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'update_historico'])->name('flota.update_historico');
    Route::get('get-recursos', [App\Http\Controllers\FlotaGeneralController::class, 'getRecursosJSON'])->name('getRecursosJSON');

    Route::post('/get-equipos-sin-funcionar-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposSinFuncionarJSON'])->name('get-equipos-sin-funcionar-json');
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
});
