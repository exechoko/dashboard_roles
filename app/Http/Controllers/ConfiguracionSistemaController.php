<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnvActualizarRequest;
use App\Http\Requests\IaActualizarRequest;
use App\Http\Requests\RestaurarBackupRequest;
use App\Http\Requests\WorkersActualizarRequest;
use App\Jobs\GenerarBackupBaseDatos;
use App\Jobs\RestaurarBackupBaseDatos;
use App\Services\AuditoriaService;
use App\Services\BackupBaseDatosService;
use App\Services\EnvEditorService;
use App\Support\ConfiguracionCatalogo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class ConfiguracionSistemaController extends Controller
{
    /**
     * Endpoints de prueba de conexión por servicio: nombre corto => URL configurada.
     *
     * @var array<string, string|null>
     */
    private const SERVICIOS_PROBABLES = [
        'ollama'    => null, // resuelto dinámicamente (services.ia.ollama_url)
        'whisper'   => null,
        'rag'       => null,
        'opencode'  => null,
        'nominatim' => null,
    ];

    public function __construct()
    {
        $this->middleware('permission:ver-menu-configuracion-sistema')->only(['index']);
        $this->middleware('permission:ver-configuracion-env')->only(['env']);
        $this->middleware('permission:editar-configuracion-env')->only(['envUpdate']);
        $this->middleware('permission:ver-configuracion-ia')->only(['ia', 'probarConexion']);
        $this->middleware('permission:editar-configuracion-ia')->only(['iaUpdate']);
        $this->middleware('permission:ver-configuracion-workers')->only(['workers']);
        $this->middleware('permission:editar-configuracion-workers')->only(['workersUpdate', 'jobsReintentar', 'jobsPurgar']);
        $this->middleware('permission:ver-configuracion-backup')->only(['backups', 'backupEstado']);
        $this->middleware('permission:crear-configuracion-backup')->only(['backupCrear']);
        $this->middleware('permission:descargar-configuracion-backup')->only(['backupDescargar']);
        $this->middleware('permission:restaurar-configuracion-backup')->only(['backupRestaurar']);
        $this->middleware('permission:borrar-configuracion-backup')->only(['backupEliminar']);
    }

    public function index(): View
    {
        return view('configuracion.index');
    }

    public function env(EnvEditorService $env): View
    {
        $valoresActuales = $env->pares();

        return view('configuracion.env', [
            'grupos'          => collect(ConfiguracionCatalogo::grupos())->except(['ia', 'workers'])->all(),
            'valores'         => $this->valoresParaFormulario(ConfiguracionCatalogo::todasLasClaves(), $valoresActuales),
            'avanzado'        => $this->clavesAvanzadas($valoresActuales),
            'grupoCritico'    => ConfiguracionCatalogo::grupoCritico(),
            'puedeCritico'    => auth()->user()?->can('editar-configuracion-env-critico') === true,
        ]);
    }

    public function envUpdate(EnvActualizarRequest $request, EnvEditorService $env): RedirectResponse
    {
        $cambios = $this->cambiosEfectivos($request->input('valores', []), $env->pares());

        if ($cambios === []) {
            return back()->with('info', 'No hubo cambios para guardar.');
        }

        try {
            $env->actualizar($cambios);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo guardar: ' . $e->getMessage());
        }

        AuditoriaService::registrar('ACTUALIZAR', 'configuracion_sistema_env', 'claves: ' . implode(', ', array_keys($cambios)));

        return back()->with('success', 'Variables de entorno actualizadas. Los cambios ya están activos.');
    }

    public function ia(EnvEditorService $env): View
    {
        $valoresActuales = $env->pares();

        return view('configuracion.ia', [
            'valores' => $this->valoresParaFormulario(ConfiguracionCatalogo::metaGrupo('ia'), $valoresActuales),
        ]);
    }

    public function iaUpdate(IaActualizarRequest $request, EnvEditorService $env): RedirectResponse
    {
        $cambios = $this->cambiosEfectivos($request->input('valores', []), $env->pares());

        if ($cambios === []) {
            return back()->with('info', 'No hubo cambios para guardar.');
        }

        try {
            $env->actualizar($cambios);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo guardar: ' . $e->getMessage());
        }

        AuditoriaService::registrar('ACTUALIZAR', 'configuracion_sistema_ia', 'claves: ' . implode(', ', array_keys($cambios)));

        return back()->with('success', 'Configuración de IA actualizada.');
    }

    public function probarConexion(string $servicio): JsonResponse
    {
        $url = match ($servicio) {
            'ollama'    => config('services.ia.ollama_url'),
            'whisper'   => config('services.ia.whisper_url'),
            'rag'       => config('services.ia.rag_url'),
            'opencode'  => config('services.opencode.url'),
            'nominatim' => config('services.nominatim.base_url'),
            default     => null,
        };

        if (!array_key_exists($servicio, self::SERVICIOS_PROBABLES) || !$url) {
            return response()->json(['ok' => false, 'error' => 'Servicio desconocido.'], 404);
        }

        try {
            $respuesta = Http::timeout(5)->get($url);

            return response()->json(['ok' => $respuesta->successful() || $respuesta->status() < 500, 'status' => $respuesta->status()]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function workers(EnvEditorService $env): View
    {
        $valoresActuales = $env->pares();
        $fallidos = DB::getSchemaBuilder()->hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('failed_at')->limit(50)->get()
            : collect();

        return view('configuracion.workers', [
            'meta'     => ConfiguracionCatalogo::metaGrupo('workers'),
            'valores'  => $this->valoresParaFormulario(ConfiguracionCatalogo::metaGrupo('workers'), $valoresActuales),
            'fallidos' => $fallidos,
        ]);
    }

    public function workersUpdate(WorkersActualizarRequest $request, EnvEditorService $env): RedirectResponse
    {
        $cambios = $this->cambiosEfectivos($request->input('valores', []), $env->pares());

        if ($cambios === []) {
            return back()->with('info', 'No hubo cambios para guardar.');
        }

        try {
            $env->actualizar($cambios);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo guardar: ' . $e->getMessage());
        }

        AuditoriaService::registrar('ACTUALIZAR', 'configuracion_sistema_workers', 'claves: ' . implode(', ', array_keys($cambios)));

        return back()->with('success', 'Configuración de colas actualizada.');
    }

    public function jobsReintentar(?string $id = null): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$id ?? 'all']]);
        AuditoriaService::registrar('ACTUALIZAR', 'configuracion_sistema_workers', 'reintentar job: ' . ($id ?? 'todos'));

        return back()->with('success', $id ? 'Job reencolado.' : 'Todos los jobs fallidos fueron reencolados.');
    }

    public function jobsPurgar(): RedirectResponse
    {
        Artisan::call('queue:flush');
        AuditoriaService::registrar('BORRAR', 'configuracion_sistema_workers', 'purgar jobs fallidos');

        return back()->with('success', 'Se eliminó el historial de jobs fallidos.');
    }

    public function backups(BackupBaseDatosService $backups): View
    {
        return view('configuracion.backups', [
            'backups'    => $backups->listar(),
            'disponible' => $backups->binariosDisponibles(),
            'estado'     => $backups->estado(),
        ]);
    }

    /**
     * Polling del estado de la generación/restauración en curso. La operación
     * en sí corre en un Job (ver GenerarBackupBaseDatos/RestaurarBackupBaseDatos):
     * hacerla en el request HTTP se cortaría atrás de Cloudflare en producción
     * (~100s) para una base grande.
     */
    public function backupEstado(BackupBaseDatosService $backups): JsonResponse
    {
        return response()->json($backups->estado() ?? ['estado' => 'inactivo']);
    }

    public function backupCrear(Request $request, BackupBaseDatosService $backups): RedirectResponse
    {
        $request->validate(['nota' => 'nullable|string|max:255']);

        if (($backups->estado()['estado'] ?? null) === 'procesando') {
            return back()->with('error', 'Ya hay un backup en curso. Esperá a que termine.');
        }

        if (!$backups->binariosDisponibles()) {
            return back()->with('error', 'No se encontró mysqldump. Configurá su ruta en Variables de Entorno > General > MYSQL_BIN_PATH.');
        }

        $nota = $request->input('nota');
        $backups->marcarPendiente('crear', null, $nota);
        GenerarBackupBaseDatos::dispatch($nota, auth()->id());

        return back()->with('success', 'Backup encolado: se está generando en segundo plano. Esta pantalla se actualiza sola cuando termine.');
    }

    public function backupDescargar(string $archivo, BackupBaseDatosService $backups): Response
    {
        try {
            $ruta = $backups->ruta($archivo);
        } catch (Throwable $e) {
            abort(404, $e->getMessage());
        }

        AuditoriaService::registrar('VER', 'configuracion_sistema_backup', "descarga: {$archivo}");

        return response()->download($ruta);
    }

    public function backupRestaurar(RestaurarBackupRequest $request, string $archivo, BackupBaseDatosService $backups): RedirectResponse
    {
        if (($backups->estado()['estado'] ?? null) === 'procesando') {
            return back()->with('error', 'Ya hay una operación de backup en curso. Esperá a que termine.');
        }

        $backups->marcarPendiente('restaurar', $archivo);
        RestaurarBackupBaseDatos::dispatch($archivo, auth()->id());

        return back()->with('success', "Restauración de {$archivo} encolada: se está ejecutando en segundo plano. Esta pantalla se actualiza sola cuando termine.");
    }

    public function backupEliminar(string $archivo, BackupBaseDatosService $backups): RedirectResponse
    {
        try {
            $backups->eliminar($archivo);
        } catch (Throwable $e) {
            return back()->with('error', 'No se pudo eliminar: ' . $e->getMessage());
        }

        AuditoriaService::registrar('BORRAR', 'configuracion_sistema_backup', "archivo: {$archivo}");

        return back()->with('success', 'Backup eliminado.');
    }

    /**
     * Arma el array clave => valor a mostrar en el formulario: las sensibles
     * se dejan vacías (nunca se manda el secreto real al navegador; en blanco
     * significa "sin cambios" al guardar).
     *
     * @param  array<string, array<string, mixed>>  $meta
     * @param  array<string, string>  $valoresActuales
     * @return array<string, string>
     */
    private function valoresParaFormulario(array $meta, array $valoresActuales): array
    {
        $resultado = [];
        foreach (array_keys($meta) as $clave) {
            $resultado[$clave] = ConfiguracionCatalogo::esSensible($clave)
                ? ''
                : ($valoresActuales[$clave] ?? '');
        }

        return $resultado;
    }

    /**
     * Claves presentes en el .env que no pertenecen a ningún grupo del
     * catálogo (pestaña "Avanzado" de la pantalla de variables de entorno).
     *
     * @param  array<string, string>  $valoresActuales
     * @return array<string, string>
     */
    private function clavesAvanzadas(array $valoresActuales): array
    {
        $catalogadas = array_keys(ConfiguracionCatalogo::todasLasClaves());
        $bloqueadas = ConfiguracionCatalogo::clavesBloqueadas();

        $avanzado = [];
        foreach ($valoresActuales as $clave => $valor) {
            if (in_array($clave, $catalogadas, true) || in_array($clave, $bloqueadas, true)) {
                continue;
            }

            $avanzado[$clave] = ConfiguracionCatalogo::esSensible($clave) ? '' : $valor;
        }

        ksort($avanzado);

        return $avanzado;
    }

    /**
     * Compara lo enviado contra el valor actual y descarta lo que no cambió.
     * Un campo sensible en blanco significa "no tocar" y se descarta siempre.
     * Una clave que nunca estuvo en el .env (usa el default del config) y llega
     * en blanco tampoco se escribe: el formulario manda TODOS los campos del
     * catálogo en cada guardado, no solo el que se tocó, y crear la clave vacía
     * pisaría el default de config (p. ej. `(int) env('X', 4000)` pasaría a 0).
     *
     * @param  array<string, string|null>  $enviados
     * @param  array<string, string>  $valoresActuales
     * @return array<string, string>
     */
    private function cambiosEfectivos(array $enviados, array $valoresActuales): array
    {
        $cambios = [];
        foreach ($enviados as $clave => $valor) {
            $valor = (string) $valor;

            if (ConfiguracionCatalogo::esSensible($clave) && $valor === '') {
                continue;
            }

            if ($valor === '' && !array_key_exists($clave, $valoresActuales)) {
                continue;
            }

            if (($valoresActuales[$clave] ?? null) === $valor) {
                continue;
            }

            $cambios[$clave] = $valor;
        }

        return $cambios;
    }
}
