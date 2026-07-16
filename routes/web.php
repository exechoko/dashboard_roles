<?php

use App\Http\Controllers\AudioTranscriptionController;
use App\Http\Controllers\BodycamController;
use App\Http\Controllers\EntregasBodycamsController;
use App\Http\Controllers\EntregasCombustibleController;
use App\Http\Controllers\ConstanciasCredencialesController;
use App\Http\Controllers\EntregasEquiposController;
use App\Http\Controllers\PasswordVaultController;
use App\Http\Controllers\PatrimonioBienController;
use App\Http\Controllers\PatrimonioTipoBienController;
use App\Http\Controllers\TareaController;
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
use App\Http\Controllers\MapaController;
use App\Http\Controllers\TipoCamaraController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CamaraFisicaController;
use App\Http\Controllers\CecocoRecursoAliasController;
use App\Http\Controllers\SitioController;
use App\Http\Controllers\CecocoController;
use App\Http\Controllers\TranscripcionController;
use App\Http\Controllers\RAGController;
use App\Http\Controllers\PlanoEdificioController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ManualesController;
use App\Http\Controllers\WebAdminController;
use App\Http\Controllers\NoticiaController;
use App\Http\Controllers\WebDependenciaController;
use App\Http\Controllers\WebGaleriaImagenController;
use App\Http\Controllers\WebHistoriaCardController;
use App\Http\Controllers\WebTechCardController;
use App\Http\Controllers\ArmaRetencionController;
use App\Http\Controllers\ArmaMotivoController;
use App\Http\Controllers\ArmaTipoController;
use App\Http\Controllers\ArmaPersonalController;

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
    // 🔹 ADMINISTRAR WEB (div911.stper.com.ar)
    Route::get('web-admin/contadores', [WebAdminController::class, 'editContadores'])
        ->name('web-admin.contadores.edit')
        ->middleware('can:editar-web-contadores');
    Route::put('web-admin/contadores', [WebAdminController::class, 'updateContadores'])
        ->name('web-admin.contadores.update')
        ->middleware('can:editar-web-contadores');
    Route::get('web-admin/textos', [WebAdminController::class, 'editTextos'])
        ->name('web-admin.textos.edit')
        ->middleware('can:editar-web-textos');
    Route::put('web-admin/textos', [WebAdminController::class, 'updateTextos'])
        ->name('web-admin.textos.update')
        ->middleware('can:editar-web-textos');
    Route::post('web-admin/textos/preview', [WebAdminController::class, 'previewTextos'])
        ->name('web-admin.textos.preview')
        ->middleware('can:editar-web-textos');
    Route::get('noticias/imagen/{archivo}', [NoticiaController::class, 'imagen'])->name('noticias.imagen');
    Route::resource('noticias', NoticiaController::class)->except(['show']);
    Route::resource('web-dependencias', WebDependenciaController::class)
        ->except(['show'])
        ->parameters(['web-dependencias' => 'dependencia']);
    Route::get('web-historia/imagen/{archivo}', [WebHistoriaCardController::class, 'imagen'])->name('web-historia.imagen');
    Route::resource('web-historia', WebHistoriaCardController::class)
        ->except(['show'])
        ->parameters(['web-historia' => 'card']);
    Route::get('web-tecnologia/imagen/{archivo}', [WebTechCardController::class, 'imagen'])->name('web-tecnologia.imagen');
    Route::resource('web-tecnologia', WebTechCardController::class)
        ->except(['show'])
        ->parameters(['web-tecnologia' => 'card']);
    Route::get('web-galeria/imagen', [WebGaleriaImagenController::class, 'imagen'])->name('web-galeria.imagen');
    Route::resource('web-galeria', WebGaleriaImagenController::class)
        ->except(['show'])
        ->parameters(['web-galeria' => 'imagen']);
    Route::get('web-admin/preview', [WebAdminController::class, 'previewWeb'])
        ->name('web-admin.preview')
        ->middleware('permission:ver-menu-web|editar-web-contadores|editar-web-textos|editar-web-historia|editar-web-tecnologia|editar-web-dependencias|editar-web-galeria');

    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('blogs', BlogController::class);
    Route::resource('equipos', EquipoController::class);
    Route::resource('terminales', TipoTerminalController::class);
    Route::resource('bodycams', BodycamController::class);
    
    // 🔹 PERSONAL EFECTIVO (NUEVO)
    Route::get('tareas/personal-efectivo', [PersonalController::class, 'index'])->name('personal.efectivo.index');

    // 🔹 TAREAS (CRUD)
    Route::resource('tareas', TareaController::class)->except(['show']);

    // 🔹 ITEMS DE TAREAS
    Route::patch('tareas-items/{id}', [TareaController::class, 'updateItem'])->name('tareas.items.update');

    Route::post('/profile/update', [UsuarioController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/update-password', [UsuarioController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::post('/profile/update-master-password', [UsuarioController::class, 'updateMasterPassword'])->name('profile.updateMasterPassword');
    Route::post('/profile/update-theme', [UsuarioController::class, 'updateTheme'])->name('profile.updateTheme')->middleware('auth');

    Route::get('dependencias/crear-general', [DependenciaController::class, 'createGeneral'])->name('dependencias.crear-general');
    Route::post('dependencias/store-general', [DependenciaController::class, 'storeGeneral'])->name('dependencias.store-general');
    Route::get('get-posibles-padres', [DependenciaController::class, 'getPosiblesPadres'])->name('getPosiblesPadres');
    Route::get('get-dependencias-por-tipo', [DependenciaController::class, 'getDependenciasPorTipo'])->name('getDependenciasPorTipo');
    Route::get('get-departamentales', [App\Http\Controllers\DependenciaController::class, 'getDepartamentales'])->name('getDepartamentales');
    Route::get('get-divisiones', [App\Http\Controllers\DependenciaController::class, 'getDivisiones'])->name('getDivisiones');
    Route::get('get-comisarias', [App\Http\Controllers\DependenciaController::class, 'getComisarias'])->name('getComisarias');
    Route::get('dependencias/{id}/jurisdiccion', [DependenciaController::class, 'jurisdiccionShow'])->name('dependencias.jurisdiccion.show');
    Route::put('dependencias/{id}/jurisdiccion', [DependenciaController::class, 'jurisdiccionUpdate'])->name('dependencias.jurisdiccion.update');
    Route::resource('dependencias', DependenciaController::class);
    Route::resource('vehiculos', VehiculoController::class);
    Route::resource('recursos', RecursoController::class);
    Route::resource('cecoco/recursos-alias', CecocoRecursoAliasController::class)
        ->parameters(['recursos-alias' => 'cecocoRecursoAlias'])
        ->names('cecoco.recursos-alias');
    Route::resource('flota', FlotaGeneralController::class);
    Route::resource('camaras', CamaraController::class);
    Route::resource('camaras_fisicas', CamaraFisicaController::class)->only(['index']);
    Route::resource('mapa', MapaController::class);
    Route::resource('tipo-camara', TipoCamaraController::class);
    Route::resource('auditoria', AuditoriaController::class)->only(['index']);
    Route::resource('sitios', SitioController::class);
    Route::resource('plano-edificio', PlanoEdificioController::class)->only(['index']);

    // API endpoints para el plano del edificio
    Route::prefix('api/plano-edificio')->group(function () {
        Route::get('/devices', [PlanoEdificioController::class, 'getDevices']);
        Route::get('/devices/{id}', [PlanoEdificioController::class, 'getDevice']);
        Route::post('/devices', [PlanoEdificioController::class, 'store']);
        Route::put('/devices/{id}', [PlanoEdificioController::class, 'update']);
        Route::put('/devices/{id}/position', [PlanoEdificioController::class, 'updatePosition']);
        Route::delete('/devices/{id}', [PlanoEdificioController::class, 'destroy']);
        Route::get('/devices/{id}/credentials', [PlanoEdificioController::class, 'getCredentials']);
        Route::get('/export', [PlanoEdificioController::class, 'export']);
    });

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::get('/generate-docx/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'generateDocxConTabla'])->name('generateDocxConTabla');
    Route::get('/ver-historico/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'verHistorico'])->name('verHistorico');
    Route::get('/flota/historico/{id}/imprimir', [FlotaGeneralController::class, 'imprimirHistorico'])->name('flota.historico.imprimir');
    Route::get('/ver-historico-desde-equipo/{id}', [App\Http\Controllers\EquipoController::class, 'verHistoricoDesdeEquipo'])->name('verHistoricoDesdeEquipo');
    Route::get('/busqueda-avanzada', [App\Http\Controllers\FlotaGeneralController::class, 'busquedaAvanzada'])->name('flota.busquedaAvanzada');
    Route::get('/busqueda-avanzada/export-excel', [App\Http\Controllers\FlotaGeneralController::class, 'exportExcelBusquedaAvanzada'])->name('flota.busquedaAvanzada.export');
    Route::post('/update-historico/{id}', [App\Http\Controllers\FlotaGeneralController::class, 'update_historico'])->name('flota.update_historico');
    Route::post('/flota/patrimoniar-rapido', [App\Http\Controllers\FlotaGeneralController::class, 'patrimoniarRapido'])->name('flota.patrimoniar-rapido');
    Route::get('get-recursos', [App\Http\Controllers\FlotaGeneralController::class, 'getRecursosJSON'])->name('getRecursosJSON');
    Route::post('flota/issi-sugerido', [App\Http\Controllers\FlotaGeneralController::class, 'generarIssiSugerido'])->name('flota.issi-sugerido');

    Route::post('/get-equipos-sin-funcionar-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposSinFuncionarJSON'])->name('get-equipos-sin-funcionar-json');
    Route::post('/get-equipos-baja-json', [App\Http\Controllers\DashboardController::class, 'getCantidadEquiposBajaJSON'])->name('get-equipos-baja-json');
    Route::post('/get-equipos-en-revision-json', [App\Http\Controllers\DashboardController::class, 'getEquiposEnRevisionJSON'])->name('get-equipos-en-revision-json');
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
    Route::get('/personal', [PersonalController::class, 'index']);
    Route::post('/personal', [PersonalController::class, 'store']);
    Route::delete('/personal/{id}', [PersonalController::class, 'destroy']);
    Route::put('/personal/{id}', [PersonalController::class, 'update']);

    //Route::get('/showmap', [App\Http\Controllers\MapaController::class, 'showMap'])->name('mapa.showMap');
    Route::post('/import-camaras', [App\Http\Controllers\CamaraController::class, 'importExcel'])->name('camaras.import');
    Route::get('/export-camaras', [App\Http\Controllers\CamaraController::class, 'exportExcel'])->name('camaras.export');
    Route::get('/export-sitios', [App\Http\Controllers\SitioController::class, 'exportExcel'])->name('sitios.export');
    Route::get('/export-equipos', [App\Http\Controllers\EquipoController::class, 'exportExcel'])->name('equipos.export');
    Route::post('/import-camaras-fisicas', [App\Http\Controllers\CamaraFisicaController::class, 'importExcel'])->name('camaras-fisicas.import');

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
    Route::get('/camaras/{id}/snapshot', [CamaraController::class, 'snapshot'])->name('camaras.snapshot');
    Route::get('/camaras/{id}/stream', [CamaraController::class, 'stream'])->name('camaras.stream');
    Route::get('/camaras/{id}/test-conexion', [CamaraController::class, 'testConexion'])->name('camaras.test-conexion');

    Route::get('/transcribir', [TranscripcionController::class, 'index'])->name('transcribe.index');
    Route::post('/transcribir', [TranscripcionController::class, 'transcribe'])->name('transcribe.audio');
    Route::get('/transcribir/estado/{jobId}', [TranscripcionController::class, 'estado'])->name('transcribe.estado');
    Route::get('/transcribir/historial', [TranscripcionController::class, 'historial'])->name('transcribe.historial');
    Route::get('/transcribir/buscar-nombre', [TranscripcionController::class, 'buscarPorNombre'])->name('transcribe.buscarNombre');
    Route::get('/transcribir/buscar-telefono', [TranscripcionController::class, 'buscarPorTelefono'])->name('transcribe.buscarTelefono');
    Route::get('/transcribir/ver/{id}', [TranscripcionController::class, 'show'])->name('transcribe.show');
    Route::put('/transcribir/actualizar/{id}', [TranscripcionController::class, 'update'])->name('transcribe.update');

    // CallAnalysis — Análisis de llamadas 911 (puerto 8082)
    Route::prefix('call-analysis')->name('callanalysis.')->group(function () {
        Route::post('/submit',         [App\Http\Controllers\CallAnalysisController::class, 'submit'])->name('submit');
        Route::get('/estado/{jobId}',  [App\Http\Controllers\CallAnalysisController::class, 'estado'])->name('estado');
        Route::get('/health',          [App\Http\Controllers\CallAnalysisController::class, 'health'])->name('health');
    });

    // RAG — Servidor IA local
    Route::prefix('rag')->name('rag.')->group(function () {
        Route::get('/',                              [RAGController::class, 'index'])->name('index');
        Route::get('/estado',                        [RAGController::class, 'estado'])->name('estado');
        Route::get('/colecciones',                   [RAGController::class, 'colecciones'])->name('colecciones');
        Route::post('/tematicas',                    [RAGController::class, 'crearTematica'])->name('tematicas.crear');
        Route::delete('/tematicas/{coleccion}',      [RAGController::class, 'eliminarTematica'])->name('tematicas.eliminar');
        Route::post('/cargar',                       [RAGController::class, 'cargar'])->name('cargar');
        Route::get('/carga-estado/{jobId}',          [RAGController::class, 'estadoCarga'])->name('carga.estado');
        Route::post('/preguntar',                    [RAGController::class, 'preguntar'])->name('preguntar');
        Route::post('/reindexar',                    [RAGController::class, 'reindexar'])->name('reindexar');
        Route::post('/jobs/{jobId}/reintentar',      [RAGController::class, 'reintentarCarga'])->name('jobs.reintentar');
        Route::get('/consulta-estado/{jobId}',       [RAGController::class, 'estadoConsulta'])->name('consulta.estado');
        Route::get('/historial',                     [RAGController::class, 'historialChat'])->name('historial');
        Route::get('/documentos',                    [RAGController::class, 'historialDocumentos'])->name('documentos');
    });

    Route::get('/transcription', [AudioTranscriptionController::class, 'index'])->name('transcription.index');
    Route::post('/generate-upload-url', [AudioTranscriptionController::class, 'generateUploadUrl']);
    Route::post('/upload-file', [AudioTranscriptionController::class, 'uploadFile']);
    Route::get('/get-results', [AudioTranscriptionController::class, 'getResults']);
    Route::get('/get-results-by-filename', [AudioTranscriptionController::class, 'getResultsByFileName']);
    Route::get('/get-historial', [AudioTranscriptionController::class, 'getHistorial'])->name('getHistorial');

    Route::get('entrega-equipos/buscar-equipos', [EntregasEquiposController::class, 'buscarEquipos'])
        ->name('entrega-equipos.buscar-equipos')
        ->middleware('can:crear-entrega-equipos');

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
        // Duplicar una entrega existente
        Route::post('{id}/duplicar', [EntregasEquiposController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:crear-entrega-equipos');
    });
    // Dashboard de entregas
    Route::get('entregas/dashboard', [EntregasEquiposController::class, 'dashboard'])
        ->name('entregas.dashboard')
        ->middleware('can:ver-menu-entregas');
    Route::get('entrega-equipos/{id}/devolver', [EntregasEquiposController::class, 'devolver'])
        ->name('entrega-equipos.devolver');

    Route::post('entrega-equipos/{id}/procesar-devolucion', [EntregasEquiposController::class, 'procesarDevolucion'])
        ->name('entrega-equipos.procesar-devolucion');

    Route::get('entrega-equipos/{entregaId}/devolucion/{devolucionId}', [EntregasEquiposController::class, 'mostrarDevolucion'])
        ->name('entrega-equipos.devolucion.detalle');

    Route::delete('entrega-equipos/{entregaId}/devolucion/{devolucionId}', [EntregasEquiposController::class, 'eliminarDevolucion'])
        ->name('entrega-equipos.devolucion.eliminar');

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

    });

    // Rutas para devolución de bodycams
    Route::get('entrega-bodycams/{id}/devolver', [EntregasBodycamsController::class, 'devolver'])
        ->name('entrega-bodycams.devolver');

    Route::post('entrega-bodycams/{id}/procesar-devolucion', [EntregasBodycamsController::class, 'procesarDevolucion'])
        ->name('entrega-bodycams.procesar-devolucion');

    Route::get('entrega-bodycams/{entregaId}/devolucion/{devolucionId}', [EntregasBodycamsController::class, 'mostrarDevolucion'])
        ->name('entrega-bodycams.devolucion.detalle');

    // Ruta para descargar el archivo generado
    Route::get('entrega-bodycams/{id}/descargar', [EntregasBodycamsController::class, 'descargarArchivo'])
        ->name('entrega-bodycams.descargar');


    Route::resource('entrega-combustible', EntregasCombustibleController::class)->names([
        'index' => 'entrega-combustible.index',
        'create' => 'entrega-combustible.create',
        'store' => 'entrega-combustible.store',
        'show' => 'entrega-combustible.show',
        'edit' => 'entrega-combustible.edit',
        'update' => 'entrega-combustible.update',
        'destroy' => 'entrega-combustible.destroy',
    ]);

    Route::get('entrega-combustible/{entrega_combustible}/documento', [EntregasCombustibleController::class, 'generarDocumento'])
        ->name('entrega-combustible.documento')
        ->middleware('can:crear-entrega-combustible');
    Route::get('entrega-combustible/{entrega_combustible}/descargar', [EntregasCombustibleController::class, 'descargarArchivo'])
        ->name('entrega-combustible.descargar');
    Route::post('entrega-combustible/{entrega_combustible}/acta-firmada', [EntregasCombustibleController::class, 'subirActaFirmada'])
        ->name('entrega-combustible.acta-firmada')
        ->middleware('can:editar-entrega-combustible');

    // Constancias de credenciales CAR911
    Route::prefix('constancias-credenciales')->name('constancias-credenciales.')->group(function () {
        Route::get('/', [ConstanciasCredencialesController::class, 'index'])->name('index');
        Route::get('crear', [ConstanciasCredencialesController::class, 'create'])->name('create');
        Route::post('/', [ConstanciasCredencialesController::class, 'store'])->name('store');
        Route::get('buscar/usuarios', [ConstanciasCredencialesController::class, 'buscarUsuarios'])->name('buscar-usuarios');
        Route::get('{id}', [ConstanciasCredencialesController::class, 'show'])->name('show');
        Route::get('{id}/editar', [ConstanciasCredencialesController::class, 'edit'])->name('edit');
        Route::put('{id}', [ConstanciasCredencialesController::class, 'update'])->name('update');
        Route::delete('{id}', [ConstanciasCredencialesController::class, 'destroy'])->name('destroy');
        Route::get('{id}/descargar', [ConstanciasCredencialesController::class, 'descargarDocumento'])->name('descargar');
        Route::post('{id}/acta-firmada', [ConstanciasCredencialesController::class, 'uploadActaFirmada'])->name('upload-acta-firmada');
        Route::post('{id}/enviar-email', [ConstanciasCredencialesController::class, 'enviarEmail'])->name('enviar-email');
    });

    // Pantalla de verificación de contraseña maestra (sin el middleware para no crear loop)
    Route::get('password-vault-auth', [PasswordVaultController::class, 'masterPasswordForm'])
        ->name('password-vault.master-password');
    Route::post('password-vault-auth', [PasswordVaultController::class, 'verifyMasterPassword'])
        ->name('password-vault.verify-master-password');

    // Rutas del gestor de contraseñas protegidas con contraseña maestra
    Route::middleware('master.password')->group(function () {
        Route::resource('password-vault', PasswordVaultController::class);
        // Generar contraseña aleatoria
        Route::get('password-vault-generate', [PasswordVaultController::class, 'generatePassword'])
            ->name('password-vault.generate');
        Route::post('password-vault/share-bulk', [PasswordVaultController::class, 'bulkShare'])
            ->name('password-vault.bulk-share');
        // Toggle favorito
        Route::post('password-vault/{passwordVault}/toggle-favorite', [PasswordVaultController::class, 'toggleFavorite'])
            ->name('password-vault.toggle-favorite');
        // Obtener contraseña (API)
        Route::get('password-vault/{passwordVault}/get-password', [PasswordVaultController::class, 'getPassword'])
            ->name('password-vault.get-password');
        // Compartir contraseña con otro usuario
        Route::post('password-vault/{passwordVault}/share', [PasswordVaultController::class, 'share'])
            ->name('password-vault.share');
        // Obtener lista de usuarios con los que se compartió
        Route::get('password-vault/{passwordVault}/shares', [PasswordVaultController::class, 'getShares'])
            ->name('password-vault.get-shares');
        // Revocar acceso compartido
        Route::delete('password-shares/{share}/revoke', [PasswordVaultController::class, 'revokeShare'])
            ->name('password-vault.revoke-share');
        Route::patch('password-shares/{share}/permission', [PasswordVaultController::class, 'updateSharePermission'])
            ->name('password-vault.update-share-permission');
    });

    Route::prefix('patrimonio')->name('patrimonio.')->group(function () {
        // Dashboard patrimonial
        Route::get('dashboard', [\App\Http\Controllers\PatrimonioCargoController::class, 'dashboard'])->name('dashboard');

        // Cargos patrimoniales
        Route::get('cargos', [\App\Http\Controllers\PatrimonioCargoController::class, 'index'])->name('cargos.index');
        Route::get('cargos/{id}/acta', [\App\Http\Controllers\PatrimonioCargoController::class, 'acta'])->name('cargos.acta');
        Route::get('cargos/{id}/generar-acta', [\App\Http\Controllers\PatrimonioCargoController::class, 'generarActa'])->name('cargos.generar-acta');
        Route::get('cargos/{id}', [\App\Http\Controllers\PatrimonioCargoController::class, 'show'])->name('cargos.show');
        Route::post('cargos/{id}/agrupar-pendientes', [\App\Http\Controllers\PatrimonioCargoController::class, 'agruparPendientes'])->name('cargos.agrupar-pendientes');
        Route::post('cargos/{id}/equipos', [\App\Http\Controllers\PatrimonioCargoController::class, 'agregarEquipo'])->name('cargos.equipos.agregar');
        Route::delete('cargos/{id}/equipos/{flotaId}', [\App\Http\Controllers\PatrimonioCargoController::class, 'quitarEquipo'])->name('cargos.equipos.quitar');
        Route::post('cargos/{id}/firmar', [\App\Http\Controllers\PatrimonioCargoController::class, 'firmar'])->name('cargos.firmar');
        Route::post('cargos/{id}/acta-firmada', [\App\Http\Controllers\PatrimonioCargoController::class, 'subirActa'])->name('cargos.subir-acta');
        Route::post('cargos/{id}/rechazar', [\App\Http\Controllers\PatrimonioCargoController::class, 'rechazar'])->name('cargos.rechazar');

        // Acciones especiales para bienes
        Route::get('bienes/{id}/baja', [PatrimonioBienController::class, 'darBaja'])->name('bienes.baja');
        Route::post('bienes/{id}/baja', [PatrimonioBienController::class, 'procesarBaja'])->name('bienes.procesarBaja');
        Route::get('bienes/{id}/traslado', [PatrimonioBienController::class, 'traslado'])->name('bienes.traslado');
        Route::post('bienes/{id}/traslado', [PatrimonioBienController::class, 'procesarTraslado'])->name('bienes.procesarTraslado');
        Route::get('bienes/items-disponibles', [PatrimonioBienController::class, 'getItemsDisponibles'])->name('bienes.items-disponibles');
        Route::post('bienes/masivo', [PatrimonioBienController::class, 'storeMasivo'])->name('bienes.store-masivo');
        // Tipos de Bien
        Route::resource('tipos-bien', PatrimonioTipoBienController::class)->except(['show']);
        // Bienes
        Route::resource('bienes', PatrimonioBienController::class);
    });

    // ── Control de Armas ──────────────────────────────────────────────────
    Route::prefix('armas')->name('armas.')->group(function () {
        // Retenciones de armas
        Route::get('retenciones', [ArmaRetencionController::class, 'index'])->name('retenciones.index');
        Route::get('retenciones/create', [ArmaRetencionController::class, 'create'])->name('retenciones.create');
        Route::post('retenciones', [ArmaRetencionController::class, 'store'])->name('retenciones.store');
        Route::get('retenciones/importar', [ArmaRetencionController::class, 'importarForm'])->name('retenciones.importar');
        Route::post('retenciones/importar', [ArmaRetencionController::class, 'importar'])->name('retenciones.importar.post');
        Route::get('retenciones/exportar', [ArmaRetencionController::class, 'exportar'])->name('retenciones.exportar');
        Route::get('retenciones/historial', [ArmaRetencionController::class, 'historial'])->name('retenciones.historial');
        Route::get('retenciones/{armaRetencion}', [ArmaRetencionController::class, 'show'])->name('retenciones.show');
        Route::get('retenciones/{armaRetencion}/edit', [ArmaRetencionController::class, 'edit'])->name('retenciones.edit');
        Route::put('retenciones/{armaRetencion}', [ArmaRetencionController::class, 'update'])->name('retenciones.update');
        Route::delete('retenciones/{armaRetencion}', [ArmaRetencionController::class, 'destroy'])->name('retenciones.destroy');
        Route::post('retenciones/{armaRetencion}/elevar', [ArmaRetencionController::class, 'elevar'])->name('retenciones.elevar');
        Route::post('retenciones/{armaRetencion}/devolver', [ArmaRetencionController::class, 'devolver'])->name('retenciones.devolver');
        Route::post('retenciones/{armaRetencion}/comentario', [ArmaRetencionController::class, 'agregarComentario'])->name('retenciones.comentario');


        // Motivos
        Route::resource('motivos', ArmaMotivoController::class)
            ->parameters(['motivos' => 'armaMotivo'])
            ->except(['show']);

        // Tipos de arma
        Route::resource('tipos', ArmaTipoController::class)
            ->parameters(['tipos' => 'armaTipo'])
            ->except(['show']);

        // Personal
        Route::get('personal', [ArmaPersonalController::class, 'index'])->name('personal.index');
        Route::get('personal/create', [ArmaPersonalController::class, 'create'])->name('personal.create');
        Route::post('personal', [ArmaPersonalController::class, 'store'])->name('personal.store');
        Route::get('personal/{personal}', [ArmaPersonalController::class, 'show'])->name('personal.show');
        Route::get('personal/{personal}/edit', [ArmaPersonalController::class, 'edit'])->name('personal.edit');
        Route::put('personal/{personal}', [ArmaPersonalController::class, 'update'])->name('personal.update');
        Route::delete('personal/{personal}', [ArmaPersonalController::class, 'destroy'])->name('personal.destroy');
        Route::post('personal/{id}/restaurar', [ArmaPersonalController::class, 'restore'])->name('personal.restore');
    });

    Route::prefix('cecoco')->name('cecoco.')->group(function () {
        Route::get('/historico-movil', [App\Http\Controllers\HistoricoMovilController::class, 'index'])->name('historico-movil');
        Route::post('/historico-movil/procesar', [App\Http\Controllers\HistoricoMovilController::class, 'procesar'])->name('historico-movil.procesar');
        Route::post('/historico-movil/exportar-excel', [App\Http\Controllers\HistoricoMovilController::class, 'exportarExcel'])->name('historico-movil.exportar-excel');
        Route::get('/historico-movil/buscar', [App\Http\Controllers\HistoricoMovilController::class, 'buscarHistorial'])->name('historico-movil.buscar');
        Route::get('/historico-movil/{historial}/cargar', [App\Http\Controllers\HistoricoMovilController::class, 'cargarHistorial'])->name('historico-movil.cargar');

        // Histórico Móvil GIS (consulta directa al GIS viewer, sin Excel)
        Route::get('/historico-movil-gis', [App\Http\Controllers\HistoricoMovilGisController::class, 'index'])->name('historico-movil-gis');
        Route::post('/historico-movil-gis/consultar', [App\Http\Controllers\HistoricoMovilGisController::class, 'consultar'])->name('historico-movil-gis.consultar');
        Route::get('/historico-movil-gis/buscar-recurso', [App\Http\Controllers\HistoricoMovilGisController::class, 'buscarRecurso'])->name('historico-movil-gis.buscar-recurso');
        Route::post('/historico-movil-gis/exportar-excel', [App\Http\Controllers\HistoricoMovilGisController::class, 'exportarExcel'])->name('historico-movil-gis.exportar-excel');
        Route::get('/historico-movil-gis/buscar', [App\Http\Controllers\HistoricoMovilGisController::class, 'buscarHistorial'])->name('historico-movil-gis.buscar');
        Route::get('/historico-movil-gis/{historial}/cargar', [App\Http\Controllers\HistoricoMovilGisController::class, 'cargarHistorial'])->name('historico-movil-gis.cargar');
        Route::delete('/historico-movil-gis/{historial}', [App\Http\Controllers\HistoricoMovilGisController::class, 'eliminarHistorial'])->name('historico-movil-gis.eliminar');
        Route::get('/', [App\Http\Controllers\EventoCecocoController::class, 'index'])->name('index');
        Route::get('/importar/form', [App\Http\Controllers\EventoCecocoController::class, 'importarForm'])->name('importar');
        Route::post('/importar', [App\Http\Controllers\EventoCecocoController::class, 'importar'])->name('importar.post');
        Route::post('/importar/hoy', [App\Http\Controllers\EventoCecocoController::class, 'importarHoy'])->name('importar.hoy');
        Route::get('/exportar/txt', [App\Http\Controllers\EventoCecocoController::class, 'exportarTxt'])->name('exportar.txt');
        Route::get('/mapa-gis', [App\Http\Controllers\GisViewerController::class, 'index'])->name('mapa-gis');
        Route::get('/mapa-gis-historico', [App\Http\Controllers\GisViewerController::class, 'indexHistorico'])->name('mapa-gis-historico');
        Route::get('/historical', [App\Http\Controllers\GisViewerController::class, 'indexHistorico'])->name('mapa-gis-historico-alias');
        Route::get('/mapa-calor', [App\Http\Controllers\EventoCecocoController::class, 'mapaCalor'])->name('mapa-calor');
        Route::get('/mapa-calor/datos', [App\Http\Controllers\EventoCecocoController::class, 'mapaCalorDatos'])->name('mapa-calor.datos');
        Route::post('/mapa-calor/geocodificar-manual', [App\Http\Controllers\EventoCecocoController::class, 'geocodificarManual'])->name('mapa-calor.geocodificar-manual');
        Route::post('/mapa-calor/geocodificar-coordenadas', [App\Http\Controllers\EventoCecocoController::class, 'geocodificarCoordenadas'])->name('mapa-calor.geocodificar-coordenadas');
        Route::get('/analitica', [App\Http\Controllers\EventoCecocoController::class, 'analitica'])->name('analitica');
        Route::get('/{eventoCecoco}/expediente', [App\Http\Controllers\EventoCecocoController::class, 'verExpediente'])->name('expediente');
        Route::get('/{eventoCecoco}', [App\Http\Controllers\EventoCecocoController::class, 'show'])->name('show');
    });

    // Proxy del Visor GIS CeCoCo — fuera del grupo prefix para admitir slashes en el path.
    Route::any('/cecoco/gis-proxy/{path?}', [App\Http\Controllers\GisViewerController::class, 'proxy'])
        ->name('cecoco.gis-proxy')
        ->where('path', '.*');

    Route::prefix('api/cecoco')->name('api.cecoco.')->group(function () {
        Route::get('/eventos', [App\Http\Controllers\EventoCecocoController::class, 'apiListar'])->name('eventos');
        Route::get('/eventos/{eventoCecoco}/grabaciones', [App\Http\Controllers\EventoCecocoController::class, 'grabaciones'])->name('grabaciones');
        Route::get('/grabacion/stream', [App\Http\Controllers\EventoCecocoController::class, 'streamGrabacion'])->name('grabacion.stream');
        Route::get('/grabacion/stream-local', [App\Http\Controllers\EventoCecocoController::class, 'streamGrabacionLocal'])->name('grabacion.stream.local');
        Route::get('/eventos/{eventoCecoco}/modulaciones', [App\Http\Controllers\EventoCecocoController::class, 'modulaciones'])->name('modulaciones');
        Route::get('/modulacion/stream', [App\Http\Controllers\EventoCecocoController::class, 'streamModulacion'])->name('modulacion.stream');
        Route::get('/eventos/{eventoCecoco}/resumen-ia', [App\Http\Controllers\EventoCecocoController::class, 'resumenIa'])->name('resumen-ia');
        Route::get('/analitica/datos', [App\Http\Controllers\EventoCecocoController::class, 'analiticaDatos'])->name('analitica.datos');
    });

    Route::get('/api/dashboard/cecoco-mapa', [App\Http\Controllers\HomeController::class, 'cecocoMapaDatos'])
        ->name('api.dashboard.cecoco-mapa');

    Route::get('/api/dashboard/workers-status', [App\Http\Controllers\HomeController::class, 'workersStatus'])
        ->name('api.dashboard.workers-status');

    Route::get('/api/dashboard/estado-cctv', [App\Http\Controllers\HomeController::class, 'estadoCctv'])
        ->name('api.dashboard.estado-cctv');

    Route::post('/api/dashboard/refresh-restauraciones', [App\Http\Controllers\HomeController::class, 'refreshRestauracionesCache'])
        ->middleware('throttle:3,1')
        ->name('api.dashboard.refresh-restauraciones');

    Route::post('/api/dashboard/refresh-restauraciones-gps', [App\Http\Controllers\HomeController::class, 'refreshRestauracionesGpsCache'])
        ->middleware('throttle:3,1')
        ->name('api.dashboard.refresh-restauraciones-gps');

    // Manuales
    Route::prefix('manuales')->group(function () {
        Route::get('/cecoco',       [ManualesController::class, 'indexCecoco'])->name('manuales.cecoco');
        Route::get('/instructivos', [ManualesController::class, 'indexInstructivos'])->name('manuales.instructivos');
        Route::post('/subir',       [ManualesController::class, 'upload'])->name('manuales.upload');
        Route::get('/ver/{id}',     [ManualesController::class, 'view'])->name('manuales.view');
        Route::get('/descargar/{id}', [ManualesController::class, 'download'])->name('manuales.download');
        Route::delete('/eliminar/{id}', [ManualesController::class, 'destroy'])->name('manuales.destroy');
    });

    // ── Análisis de Períodos 911 ──────────────────────────────────────────────
    Route::prefix('incidencias')->name('incidencias.')->group(function () {
        // Períodos
        Route::prefix('periodos')->name('periodos.')->group(function () {
            Route::get('/',                   [App\Http\Controllers\PeriodoFacturaController::class, 'index'])->name('index');
            Route::get('/create',             [App\Http\Controllers\PeriodoFacturaController::class, 'create'])->name('create');
            Route::post('/',                  [App\Http\Controllers\PeriodoFacturaController::class, 'store'])->name('store');
            Route::get('/{id}',               [App\Http\Controllers\PeriodoFacturaController::class, 'show'])->name('show');
            Route::get('/{id}/edit',          [App\Http\Controllers\PeriodoFacturaController::class, 'edit'])->name('edit');
            Route::put('/{id}',               [App\Http\Controllers\PeriodoFacturaController::class, 'update'])->name('update');
            Route::delete('/{id}',            [App\Http\Controllers\PeriodoFacturaController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/importar',      [App\Http\Controllers\PeriodoFacturaController::class, 'importarForm'])->name('importar');
            Route::post('/{id}/importar',     [App\Http\Controllers\PeriodoFacturaController::class, 'importar'])->name('importar.post');
            Route::get('/{id}/informe',           [App\Http\Controllers\PeriodoFacturaController::class, 'generarInforme'])->name('informe');
            Route::get('/{id}/recibo',            [App\Http\Controllers\PeriodoFacturaController::class, 'generarRecibo'])->name('recibo');
            Route::post('/{id}/arrastrar',        [App\Http\Controllers\PeriodoFacturaController::class, 'arrastarPersistentes'])->name('arrastrar');
        });
        // Incidencias dentro de un período
        Route::prefix('periodos/{periodoId}/incidencias')->name('incidencia.')->group(function () {
            Route::get('/create',             [App\Http\Controllers\PeriodoFacturaController::class, 'incidenciaCreate'])->name('create');
            Route::post('/',                  [App\Http\Controllers\PeriodoFacturaController::class, 'incidenciaStore'])->name('store');
            Route::get('/{incidenciaId}/edit',[App\Http\Controllers\PeriodoFacturaController::class, 'incidenciaEdit'])->name('edit');
            Route::put('/{incidenciaId}',     [App\Http\Controllers\PeriodoFacturaController::class, 'incidenciaUpdate'])->name('update');
            Route::delete('/{incidenciaId}',  [App\Http\Controllers\PeriodoFacturaController::class, 'incidenciaDestroy'])->name('destroy');
        });
        // API
        Route::get('/api/ponderacion',        [App\Http\Controllers\PeriodoFacturaController::class, 'apiPonderacion'])->name('api.ponderacion');
    });

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
