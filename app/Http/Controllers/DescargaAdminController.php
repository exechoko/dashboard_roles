<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarArchivoDescarga;
use App\Mail\CompartirAprobadoMail;
use App\Mail\CompartirRechazadoMail;
use App\Models\DescargaArchivo;
use App\Models\DescargaCategoria;
use App\Models\DescargaLinkPublico;
use App\Models\DescargaLog;
use App\Models\DescargaQrCode;
use App\Models\DescargaSolicitudCompartir;
use App\Models\User;
use App\Services\Descargas\DescargaNotificador;
use App\Services\Descargas\DescargaRepositorio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Permission\Models\Role;

class DescargaAdminController extends Controller
{
    private DescargaRepositorio $repositorio;

    public function __construct(DescargaRepositorio $repositorio)
    {
        $this->middleware('permission:administrar-plataforma-descargas');
        $this->repositorio = $repositorio;
    }

    public function index()
    {
        $totalArchivos = DescargaArchivo::activos()->count();
        $totalDescargas = DescargaArchivo::sum('descargas_count');
        $totalCategorias = DescargaCategoria::activas()->count();
        $archivosExpirados = DescargaArchivo::expirados()->count();
        $archivosDestacados = DescargaArchivo::destacados()->count();
        $archivosCompartidos = DescargaArchivo::compartidos()->count();
        $solicitudesPendientes = DescargaSolicitudCompartir::pendientes()->count();

        $ultimosArchivos = DescargaArchivo::with(['categoria', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $archivosPopulares = DescargaArchivo::with('categoria')
            ->activos()
            ->orderByDesc('descargas_count')
            ->take(10)
            ->get();

        return view('herramientas.descargas.admin.index', compact(
            'totalArchivos',
            'totalDescargas',
            'totalCategorias',
            'archivosExpirados',
            'archivosDestacados',
            'archivosCompartidos',
            'solicitudesPendientes',
            'ultimosArchivos',
            'archivosPopulares'
        ));
    }

    public function categorias()
    {
        $categorias = DescargaCategoria::withCount('archivos')
            ->ordenadas()
            ->get();

        return view('herramientas.descargas.admin.categorias', compact('categorias'));
    }

    public function storeCategoria(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'icono' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'orden' => 'nullable|integer',
        ]);

        DescargaCategoria::create($data);

        return redirect()->route('descargas.admin.categorias')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function updateCategoria(Request $request, DescargaCategoria $categoria)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'icono' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $categoria->update($data);

        return redirect()->route('descargas.admin.categorias')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroyCategoria(DescargaCategoria $categoria)
    {
        if ($categoria->archivos()->count() > 0) {
            return redirect()->route('descargas.admin.categorias')
                ->with('error', 'No se puede eliminar la categoría porque tiene archivos asociados.');
        }

        $categoria->delete();

        return redirect()->route('descargas.admin.categorias')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    public function archivos(Request $request)
    {
        $query = DescargaArchivo::with(['categoria', 'user', 'roles']);

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre_original', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        if ($request->filled('estado')) {
            switch ($request->input('estado')) {
                case 'activos':
                    $query->activos()->noExpirados();
                    break;
                case 'expirados':
                    $query->expirados();
                    break;
                case 'inactivos':
                    $query->where('activo', false);
                    break;
            }
        }

        $archivos = $query->latest()->paginate(20)->withQueryString();
        $categorias = DescargaCategoria::ordenadas()->get();

        return view('herramientas.descargas.admin.archivos', compact('archivos', 'categorias'));
    }

    public function create()
    {
        $categorias = DescargaCategoria::activas()->ordenadas()->get();
        $roles = Role::all();

        return view('herramientas.descargas.admin.create', compact('categorias', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|file',
            'archivos_config' => 'required|array',
            'archivos_config.*.categoria_id' => 'required|exists:descarga_categorias,id',
            'archivos_config.*.descripcion' => 'nullable|string|max:1000',
            'archivos_config.*.roles' => 'required|array|min:1',
            'archivos_config.*.roles.*' => 'exists:roles,id',
            'archivos_config.*.usuarios' => 'nullable|array',
            'archivos_config.*.usuarios.*' => 'exists:users,id',
            'archivos_config.*.destacado' => 'boolean',
            'archivos_config.*.expira_dias' => 'nullable|integer|min:1',
        ]);

        $archivosConfig = $request->input('archivos_config', []);
        $archivosProcesados = 0;
        $conflictos = [];
        $tempDir = 'temp_descargas';
        
        // Crear directorio temporal si no existe
        if (!Storage::exists($tempDir)) {
            Storage::makeDirectory($tempDir);
        }

        foreach ($request->file('archivos') as $index => $archivo) {
            $config = $archivosConfig[$index] ?? null;
            
            if (!$config) {
                continue;
            }

            // Verificar conflictos
            $conflicto = $this->repositorio->verificarConflicto($archivo->getClientOriginalName());

            if ($conflicto) {
                $size = $archivo->getSize();
                $mimeType = $archivo->getMimeType();
                $originalName = $archivo->getClientOriginalName();
                
                // Guardar archivo temporal
                $tempFile = $archivo->store($tempDir);
                
                $conflictos[] = [
                    'temp_path' => $tempFile,
                    'original_name' => $originalName,
                    'size' => $size,
                    'mime_type' => $mimeType,
                    'conflicto_id' => $conflicto->id,
                    'conflicto_nombre' => $conflicto->nombre_original,
                    'config' => $config,
                ];
                continue;
            }

            // Guardar archivo temporal
            $archivoTemporalPath = $archivo->store($tempDir);

            // Calcular fecha de expiración
            $expiraAt = !empty($config['expira_dias'])
                ? now()->addDays($config['expira_dias'])->toDateTimeString()
                : null;

            // Dispatch del Job
            ProcesarArchivoDescarga::dispatch(
                $archivoTemporalPath,
                $archivo->getClientOriginalName(),
                $config['categoria_id'],
                $config['descripcion'] ?? null,
                $config['roles'] ?? [],
                $config['usuarios'] ?? [],
                $expiraAt,
                !empty($config['destacado']),
                Auth::id()
            );

            $archivosProcesados++;
        }

        if (!empty($conflictos)) {
            session()->flash('conflictos', $conflictos);
            return redirect()->route('descargas.admin.resolver_conflictos');
        }

        return redirect()->route('descargas.admin.archivos')
            ->with('success', $archivosProcesados . ' archivo(s) subido(s). Se están procesando en segundo plano.');
    }

    public function resolverConflictos()
    {
        $conflictos = session('conflictos', []);

        if (empty($conflictos)) {
            return redirect()->route('descargas.admin.create');
        }

        return view('herramientas.descargas.admin.conflictos', compact('conflictos'));
    }

    public function procesarConflicto(Request $request)
    {
        $request->validate([
            'acciones' => 'required|array',
            'acciones.*.accion' => 'required|in:reemplazar,cancelar,copia',
        ]);

        $acciones = $request->input('acciones');
        $archivosCreados = [];
        $tempDir = storage_path('app/temp_descargas');

        foreach ($acciones as $index => $conflictoData) {
            $accion = $conflictoData['accion'];
            $configRaw = $conflictoData['config'] ?? '[]';
            $config = is_string($configRaw) ? json_decode($configRaw, true) : $configRaw;

            if ($accion === 'cancelar') {
                if (isset($conflictoData['temp_path']) && file_exists($conflictoData['temp_path'])) {
                    unlink($conflictoData['temp_path']);
                }
                continue;
            }

            $tempPath = $conflictoData['temp_path'];
            if (!file_exists($tempPath)) {
                continue;
            }

            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $conflictoData['original_name'],
                $conflictoData['mime_type'],
                null,
                true
            );

            if ($accion === 'reemplazar') {
                $archivoExistente = DescargaArchivo::find($conflictoData['conflicto_id']);
                if ($archivoExistente) {
                    $this->repositorio->reemplazarArchivo(
                        $archivoExistente,
                        $uploadedFile,
                        Auth::id(),
                        $conflictoData['motivo'] ?? null
                    );
                    $archivosCreados[] = $archivoExistente;
                }
            } elseif ($accion === 'copia') {
                $config['user_id'] = $config['user_id'] ?? Auth::id();
                $archivosCreados[] = $this->repositorio->cargarComoCopia(
                    DescargaArchivo::find($conflictoData['conflicto_id']),
                    $uploadedFile,
                    $config
                );
            }
        }

        if (is_dir($tempDir)) {
            array_map('unlink', glob($tempDir . '/*'));
        }

        $notificador = app(DescargaNotificador::class);
        foreach ($archivosCreados as $archivo) {
            $notificador->notificarNuevoArchivo($archivo);
        }

        return redirect()->route('descargas.admin.archivos')
            ->with('success', 'Archivos procesados correctamente.');
    }

    public function edit(DescargaArchivo $archivo)
    {
        $categorias = DescargaCategoria::activas()->ordenadas()->get();
        $roles = Role::all();
        $archivo->load('roles');

        return view('herramientas.descargas.admin.edit', compact('archivo', 'categorias', 'roles'));
    }

    public function update(Request $request, DescargaArchivo $archivo)
    {
        $data = $request->validate([
            'categoria_id' => 'required|exists:descarga_categorias,id',
            'descripcion' => 'nullable|string|max:1000',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'destacado' => 'boolean',
            'expira_at' => 'nullable|date',
        ]);

        $archivo->update($data);
        $archivo->roles()->sync($data['roles']);

        return redirect()->route('descargas.admin.archivos')
            ->with('success', 'Archivo actualizado correctamente.');
    }

    public function destroy(DescargaArchivo $archivo)
    {
        $this->repositorio->eliminarArchivo($archivo);

        return redirect()->route('descargas.admin.archivos')
            ->with('success', 'Archivo eliminado correctamente.');
    }

    public function reactivar(DescargaArchivo $archivo)
    {
        $this->repositorio->reactivarArchivo($archivo);

        return redirect()->route('descargas.admin.archivos')
            ->with('success', 'Archivo reactivado correctamente.');
    }

    public function logs(Request $request)
    {
        $query = DescargaLog::with(['archivo', 'user']);

        if ($request->filled('archivo_id')) {
            $query->where('archivo_id', $request->input('archivo_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('downloaded_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('downloaded_at', '<=', $request->input('fecha_hasta'));
        }

        $logs = $query->latest('downloaded_at')->paginate(30)->withQueryString();

        return view('herramientas.descargas.admin.logs', compact('logs'));
    }

    public function links()
    {
        $links = DescargaLinkPublico::with(['archivo', 'user'])
            ->latest('created_at')
            ->paginate(20);

        return view('herramientas.descargas.admin.links', compact('links'));
    }

    public function crearLink(Request $request)
    {
        $request->validate([
            'archivo_id' => 'required|exists:descarga_archivos,id',
            'expira_horas' => 'nullable|integer|min:1',
            'password' => 'nullable|string|min:4',
        ]);

        $archivo = DescargaArchivo::findOrFail($request->input('archivo_id'));

        $expiraHoras = $request->input('expira_horas', config('descargas.links_expiracion_horas'));

        $link = DescargaLinkPublico::create([
            'archivo_id' => $archivo->id,
            'token' => DescargaLinkPublico::generarToken(),
            'password' => $request->filled('password') ? Hash::make($request->input('password')) : null,
            'max_usos' => config('descargas.links_max_usos_default', 1),
            'expira_at' => now()->addHours($expiraHoras),
            'user_id' => Auth::id(),
            'activo' => true,
            'created_at' => now(),
        ]);

        return redirect()->route('descargas.admin.links')
            ->with('success', 'Link público generado: ' . route('descargas.link.publico', $link->token));
    }

    public function destroyLink(DescargaLinkPublico $link)
    {
        $link->update(['activo' => false]);

        return redirect()->route('descargas.admin.links')
            ->with('success', 'Link desactivado correctamente.');
    }

    public function exportarLogs(Request $request)
    {
        $query = DescargaLog::with(['archivo', 'user']);

        if ($request->filled('fecha_desde')) {
            $query->whereDate('downloaded_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('downloaded_at', '<=', $request->input('fecha_hasta'));
        }

        $logs = $query->latest('downloaded_at')->get();

        $csv = 'Fecha,Archivo,Usuario,IP' . PHP_EOL;
        foreach ($logs as $log) {
            $csv .= sprintf(
                '%s,%s,%s,%s' . PHP_EOL,
                $log->downloaded_at->format('d/m/Y H:i:s'),
                $log->archivo->nombre_original ?? 'Eliminado',
                $log->user->name ?? 'Público',
                $log->ip_address
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="logs_descargas_' . now()->format('Y-m-d') . '.csv"');
    }

    public function progreso(Request $request)
    {
        $jobs = json_decode($request->query('jobs', '[]'), true);

        return view('herramientas.descargas.admin.progreso', compact('jobs'));
    }

    public function jobStatus($jobId)
    {
        $archivo = DescargaArchivo::where('job_id', $jobId)->first();

        if (!$archivo) {
            return response()->json([
                'estado' => 'desconocido',
                'progreso' => 0,
                'error' => null,
            ]);
        }

        return response()->json([
            'estado' => $archivo->estado_proceso,
            'progreso' => $archivo->progreso,
            'error' => $archivo->error_proceso,
            'archivo_id' => $archivo->id,
        ]);
    }

    /**
     * Mostrar todas las solicitudes de compartir
     */
    public function solicitudes(Request $request)
    {
        $query = DescargaSolicitudCompartir::with([
            'archivo.categoria',
            'usuarioSolicita',
            'usuarioDestino',
            'aprobadoPor'
        ]);

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        } else {
            // Por defecto mostrar todas
        }

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->whereHas('archivo', function ($q) use ($busqueda) {
                $q->where('nombre_original', 'like', "%{$busqueda}%");
            });
        }

        $solicitudes = $query->latest()->paginate(20)->withQueryString();

        return view('herramientas.descargas.admin.solicitudes', compact('solicitudes'));
    }

    /**
     * Aprobar una solicitud de compartir
     */
    public function aprobarSolicitud(DescargaSolicitudCompartir $solicitud, Request $request)
    {
        if (!$solicitud->estaPendiente()) {
            return redirect()->route('descargas.admin.solicitudes')
                ->with('error', 'Esta solicitud ya fue procesada.');
        }

        $archivo = $solicitud->archivo;
        $usuarioDestino = $solicitud->usuarioDestino;

        // Actualizar la solicitud
        $solicitud->update([
            'estado' => 'aprobado',
            'aprobado_por' => Auth::id(),
            'respondido_at' => now(),
        ]);

        // Agregar el usuario destino a la lista de usuarios con acceso
        $archivo->usuarios()->syncWithoutDetaching([$usuarioDestino->id]);

        // Marcar el archivo como compartido
        $archivo->update([
            'es_compartido' => true,
            'compartido_por_user_id' => $solicitud->usuario_solicita_id,
        ]);

        // Enviar notificación al usuario que solicitó
        if ($solicitud->usuarioSolicita->email) {
            Mail::to($solicitud->usuarioSolicita->email)->send(new CompartirAprobadoMail($solicitud));
        }

        // Enviar notificación al usuario destino
        $notificador = app(DescargaNotificador::class);
        $notificador->notificarAccesoDirecto($archivo, $usuarioDestino);

        return redirect()->route('descargas.admin.solicitudes')
            ->with('success', 'Solicitud aprobada correctamente.');
    }

    /**
     * Rechazar una solicitud de compartir
     */
    public function rechazarSolicitud(DescargaSolicitudCompartir $solicitud, Request $request)
    {
        if (!$solicitud->estaPendiente()) {
            return redirect()->route('descargas.admin.solicitudes')
                ->with('error', 'Esta solicitud ya fue procesada.');
        }

        $request->validate([
            'motivo_respuesta' => 'nullable|string|max:1000',
        ]);

        // Actualizar la solicitud
        $solicitud->update([
            'estado' => 'rechazado',
            'aprobado_por' => Auth::id(),
            'motivo_respuesta' => $request->motivo_respuesta,
            'respondido_at' => now(),
        ]);

        // Enviar notificación al usuario que solicitó
        if ($solicitud->usuarioSolicita->email) {
            Mail::to($solicitud->usuarioSolicita->email)->send(new CompartirRechazadoMail($solicitud));
        }

        return redirect()->route('descargas.admin.solicitudes')
            ->with('success', 'Solicitud rechazada correctamente.');
    }

    /**
     * Revocar acceso de un usuario a un archivo compartido
     */
    public function revocarAcceso(DescargaArchivo $archivo, User $usuario)
    {
        // Verificar que el archivo es compartido
        if (!$archivo->es_compartido) {
            return redirect()->route('descargas.admin.archivos')
                ->with('error', 'Este archivo no es compartido.');
        }

        // Remover el usuario de la lista de usuarios con acceso
        $archivo->usuarios()->detach($usuario->id);

        // Verificar si quedan usuarios con acceso directo
        if ($archivo->usuarios()->count() === 0) {
            $archivo->update([
                'es_compartido' => false,
                'compartido_por_user_id' => null,
            ]);
        }

        return redirect()->route('descargas.show', $archivo)
            ->with('success', 'Acceso revocado correctamente.');
    }

    /**
     * Generar código QR para un archivo
     */
    public function generarQr(DescargaArchivo $archivo, Request $request)
    {
        $request->validate([
            'expira_horas' => 'nullable|integer|min:1|max:720',
            'password' => 'nullable|string|min:4',
        ]);

        $expiraHoras = $request->input('expira_horas', config('descargas.qr_default_expiracion_horas', 24));
        $password = $request->input('password');

        // Generar token único
        $token = Str::random(64);

        // Generar URL de descarga
        $urlDescarga = route('descargas.qr.descargar', $token);

        // Generar código QR
        $qrNombre = 'qr_' . $token . '.png';
        $qrPath = 'descargas/qrcodes/' . $qrNombre;

        // Asegurar que el directorio existe
        if (!Storage::exists('descargas/qrcodes')) {
            Storage::makeDirectory('descargas/qrcodes');
        }

        $qrFullPath = storage_path('app/' . $qrPath);
        QrCode::format('png')
            ->size(config('descargas.qr_tamano_px', 300))
            ->margin(1)
            ->generate($urlDescarga, $qrFullPath);

        // Crear registro en la base de datos
        $qrCode = DescargaQrCode::create([
            'archivo_id' => $archivo->id,
            'token' => $token,
            'ruta_qr' => $qrPath,
            'password' => $password ? Hash::make($password) : null,
            'max_usos' => 1,
            'usos_count' => 0,
            'expira_at' => now()->addHours($expiraHoras),
            'generado_por' => Auth::id(),
            'activo' => true,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Código QR generado exitosamente',
            'qr_id' => $qrCode->id,
            'qr_url' => route('descargas.admin.qr.descargar-imagen', $qrCode->id),
            'download_url' => $urlDescarga,
            'expira_at' => $qrCode->expira_at->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Descargar imagen QR
     */
    public function descargarImagenQr(DescargaQrCode $qrCode)
    {
        if (!Storage::exists($qrCode->ruta_qr)) {
            abort(404, 'Imagen QR no encontrada');
        }

        return response()->download(
            storage_path('app/' . $qrCode->ruta_qr),
            'qr_' . $qrCode->archivo->nombre_original . '.png'
        );
    }

    /**
     * Listar códigos QR generados
     */
    public function listarQrs(Request $request)
    {
        $query = DescargaQrCode::with(['archivo', 'generadoPorUser']);

        if ($request->filled('archivo_id')) {
            $query->where('archivo_id', $request->input('archivo_id'));
        }

        if ($request->filled('estado')) {
            switch ($request->input('estado')) {
                case 'activos':
                    $query->where('activo', true)->where('expira_at', '>', now());
                    break;
                case 'expirados':
                    $query->where('expira_at', '<=', now());
                    break;
                case 'usados':
                    $query->where('usos_count', '>=', DB::raw('max_usos'));
                    break;
            }
        }

        $qrs = $query->orderByDesc('created_at')->paginate(20);

        return view('herramientas.descargas.admin.qrs', compact('qrs'));
    }

    /**
     * Desactivar código QR
     */
    public function desactivarQr(DescargaQrCode $qrCode)
    {
        $qrCode->update(['activo' => false]);

        return redirect()->route('descargas.admin.qrs')
            ->with('success', 'Código QR desactivado correctamente');
    }
}
