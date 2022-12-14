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

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('get-departamentales', [App\Http\Controllers\DependenciaController::class, 'getDepartamentales'])->name('getDepartamentales');
    Route::get('get-divisiones', [App\Http\Controllers\DependenciaController::class, 'getDivisiones'])->name('getDivisiones');
    Route::get('get-comisarias', [App\Http\Controllers\DependenciaController::class, 'getComisarias'])->name('getComisarias');
    Route::get('/generate-docx/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'generateDocx'])->name('generateDocx');
    Route::get('/generate-docx/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTemplate'])->name('generateDocxConTemplate');
    Route::get('/generate-docx/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTabla'])->name('generateDocxConTabla');
    Route::get('/ver-historico/{id}',[App\Http\Controllers\FlotaGeneralController::class, 'verHistorico'])->name('verHistorico');

    Route::post('/get-moviles-json', [App\Http\Controllers\DashboardController::class, 'getMovilesJSON'])->name('get-moviles-json');

});
