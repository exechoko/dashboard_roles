<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudCompartirMail;
use App\Models\DescargaArchivo;
use App\Models\DescargaCategoria;
use App\Models\DescargaComentario;
use App\Models\DescargaFavorito;
use App\Models\DescargaLinkPublico;
use App\Models\DescargaLog;
use App\Models\DescargaQrCode;
use App\Models\DescargaSolicitudCompartir;
use App\Models\DescargaZipTemporal;
use App\Models\User;
use App\Services\Descargas\DescargaRepositorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DescargaController extends Controller
{
    public function __construct()
    {
        // linkPublico/descargarConQr son de acceso publico (rutas fuera del
        // grupo 'auth' en routes/web.php): la seguridad la da el token del
        // link/QR, no el login ni el permiso del sistema.
        $this->middleware('permission:ver-plataforma-descargas')
            ->except(['linkPublico', 'descargarConQr']);
    }

    public function index(Request $request)
    {
        $query = DescargaArchivo::with(['categoria', 'user', 'roles'])
            ->activos()
            ->noExpirados()
            ->accesiblesPor(Auth::user());

        // Búsqueda por texto
        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre_original', 'like', "%{$busqueda}%")
                  ->orWhere('extension', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        // Filtro por extensión
        if ($request->filled('extension')) {
            $query->where('extension', $request->input('extension'));
        }

        // Filtro por usuario que subió
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtro por fecha de subida desde
        if ($request->filled('fecha_subida_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_subida_desde'));
        }

        // Filtro por fecha de subida hasta
        if ($request->filled('fecha_subida_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_subida_hasta'));
        }

        // Filtro por tamaño mínimo (en KB)
        if ($request->filled('tamano_min')) {
            $query->where('tamano_bytes', '>=', $request->input('tamano_min') * 1024);
        }

        // Filtro por tamaño máximo (en KB)
        if ($request->filled('tamano_max')) {
            $query->where('tamano_bytes', '<=', $request->input('tamano_max') * 1024);
        }

        // Ordenamiento
        $orden = $request->input('orden', 'recientes');
        switch ($orden) {
            case 'nombre':
                $query->orderBy('nombre_original');
                break;
            case 'descargas':
                $query->orderByDesc('descargas_count');
                break;
            case 'tamano':
                $query->orderByDesc('tamano_bytes');
                break;
            case 'antiguos':
                $query->orderBy('created_at');
                break;
            default:
                $query->orderByDesc('destacado')->orderByDesc('created_at');
        }

        $archivos = $query->paginate(20)->withQueryString();
        $categorias = DescargaCategoria::activas()->ordenadas()->get();
        $extensiones = DescargaArchivo::accesiblesPor(Auth::user())
            ->activos()
            ->select('extension')
            ->distinct()
            ->pluck('extension')
            ->sort();
        
        // Obtener usuarios que han subido archivos
        $usuarios = \App\Models\User::whereHas('archivosSubidos', function ($q) {
            $q->activos()->accesiblesPor(Auth::user());
        })->select('id', 'name')->orderBy('name')->get();

        return view('herramientas.descargas.index', compact('archivos', 'categorias', 'extensiones', 'usuarios'));
    }

    public function galeria(Request $request)
    {
        $query = DescargaArchivo::with(['categoria', 'user', 'roles'])
            ->activos()
            ->noExpirados()
            ->accesiblesPor(Auth::user())
            ->whereIn('extension', ['jpg', 'jpeg', 'png', 'gif']);

        // Búsqueda por texto
        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre_original', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        // Filtro por usuario que subió
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtro por fecha de subida desde
        if ($request->filled('fecha_subida_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_subida_desde'));
        }

        // Filtro por fecha de subida hasta
        if ($request->filled('fecha_subida_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_subida_hasta'));
        }

        // Ordenamiento
        $orden = $request->input('orden', 'recientes');
        switch ($orden) {
            case 'nombre':
                $query->orderBy('nombre_original');
                break;
            case 'descargas':
                $query->orderByDesc('descargas_count');
                break;
            case 'tamano':
                $query->orderByDesc('tamano_bytes');
                break;
            case 'antiguos':
                $query->orderBy('created_at');
                break;
            default:
                $query->orderByDesc('destacado')->orderByDesc('created_at');
        }

        $archivos = $query->paginate(24)->withQueryString();
        $categorias = DescargaCategoria::activas()->ordenadas()->get();
        
        // Obtener usuarios que han subido imágenes
        $usuarios = \App\Models\User::whereHas('archivosSubidos', function ($q) {
            $q->activos()
              ->accesiblesPor(Auth::user())
              ->whereIn('extension', ['jpg', 'jpeg', 'png', 'gif']);
        })->select('id', 'name')->orderBy('name')->get();

        return view('herramientas.descargas.galeria', compact('archivos', 'categorias', 'usuarios'));
    }

    public function show(DescargaArchivo $archivo)
    {
        $this->autorizarArchivo($archivo);

        $archivo->load(['categoria', 'user', 'roles', 'comentarios.user', 'tags']);

        return view('herramientas.descargas.show', compact('archivo'));
    }

    public function download(DescargaArchivo $archivo, Request $request)
    {
        $this->autorizarArchivo($archivo);

        $repositorio = app(DescargaRepositorio::class);

        if (!$repositorio->existeArchivo($archivo)) {
            abort(404, 'El archivo no se encuentra disponible.');
        }

        $this->registrarDescarga($archivo, $request);

        return response()->download(
            $repositorio->obtenerRutaAbsoluta($archivo),
            $archivo->nombre_original
        );
    }

    public function preview(DescargaArchivo $archivo)
    {
        $this->autorizarArchivo($archivo);

        if (!$archivo->es_previeweable) {
            abort(404, 'Este tipo de archivo no admite vista previa.');
        }

        $repositorio = app(DescargaRepositorio::class);
        $ruta = $repositorio->obtenerRutaAbsoluta($archivo);

        return response()->file($ruta, [
            'Content-Type' => $archivo->mime_type,
            'Content-Disposition' => 'inline; filename="' . $archivo->nombre_archivo . '"',
        ]);
    }

    public function comentar(DescargaArchivo $archivo, Request $request)
    {
        $this->autorizarArchivo($archivo);

        $request->validate([
            'comentario' => 'required|string|max:2000',
        ]);

        $esAdmin = Auth::user()->can('administrar-plataforma-descargas');

        DescargaComentario::create([
            'archivo_id' => $archivo->id,
            'user_id' => Auth::id(),
            'comentario' => $request->input('comentario'),
            'es_admin' => $esAdmin,
        ]);

        return redirect()->route('descargas.show', $archivo)
            ->with('success', 'Comentario agregado correctamente.');
    }

    public function linkPublico(string $token, Request $request)
    {
        $link = DescargaLinkPublico::where('token', $token)
            ->with('archivo.categoria')
            ->first();

        if (!$link || !$link->es_utilizable) {
            abort(404, 'El link no existe o ha expirado.');
        }

        if ($link->requierePassword()) {
            if (!$request->has('password')) {
                return view('herramientas.descargas.link_password', compact('link'));
            }

            if (!$link->verificarPassword($request->input('password'))) {
                return view('herramientas.descargas.link_password', [
                    'link' => $link,
                    'error' => 'Contraseña incorrecta.',
                ]);
            }
        }

        $archivo = $link->archivo;

        if (!$archivo->activo || $archivo->esta_expirado) {
            abort(404, 'El archivo no está disponible.');
        }

        $repositorio = app(DescargaRepositorio::class);

        if (!$repositorio->existeArchivo($archivo)) {
            abort(404, 'El archivo no se encuentra disponible.');
        }

        $this->registrarDescarga($archivo, $request, $link->id);
        $link->registrarUso();

        return response()->download(
            $repositorio->obtenerRutaAbsoluta($archivo),
            $archivo->nombre_original
        );
    }

    private function autorizarArchivo(DescargaArchivo $archivo): void
    {
        if (!Auth::user()->can('administrar-plataforma-descargas')) {
            if (!$archivo->puedeDescargar(Auth::user())) {
                abort(403, 'No tienes permisos para acceder a este archivo.');
            }
        }
    }

    private function registrarDescarga(DescargaArchivo $archivo, Request $request, ?int $linkPublicoId = null): void
    {
        DescargaLog::create([
            'archivo_id' => $archivo->id,
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'link_publico_id' => $linkPublicoId,
            'downloaded_at' => now(),
        ]);

        $archivo->increment('descargas_count');
    }

    /**
     * Solicitar compartir un archivo con otro usuario
     */
    public function solicitarCompartir(DescargaArchivo $archivo, Request $request)
    {
        $this->autorizarArchivo($archivo);

        $request->validate([
            'usuario_destino_id' => 'required|exists:users,id',
            'motivo' => 'nullable|string|max:1000',
        ]);

        $usuarioDestino = User::find($request->usuario_destino_id);

        // Verificar que no sea el mismo usuario
        if ($usuarioDestino->id === Auth::id()) {
            return redirect()->route('descargas.show', $archivo)
                ->with('error', 'No puedes compartir un archivo contigo mismo.');
        }

        // Verificar si ya existe una solicitud pendiente
        $solicitudExistente = DescargaSolicitudCompartir::where('archivo_id', $archivo->id)
            ->where('usuario_destino_id', $usuarioDestino->id)
            ->where('estado', 'pendiente')
            ->first();

        if ($solicitudExistente) {
            return redirect()->route('descargas.show', $archivo)
                ->with('error', 'Ya existe una solicitud pendiente para compartir este archivo con este usuario.');
        }

        // Verificar si el usuario destino ya tiene acceso
        if ($archivo->puedeDescargar($usuarioDestino)) {
            return redirect()->route('descargas.show', $archivo)
                ->with('error', 'Este usuario ya tiene acceso al archivo.');
        }

        // Crear la solicitud
        $solicitud = DescargaSolicitudCompartir::create([
            'archivo_id' => $archivo->id,
            'usuario_solicita_id' => Auth::id(),
            'usuario_destino_id' => $usuarioDestino->id,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
        ]);

        // Enviar notificación a los administradores
        $admins = User::permission('administrar-plataforma-descargas')->get();
        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new SolicitudCompartirMail($solicitud));
            }
        }

        return redirect()->route('descargas.show', $archivo)
            ->with('success', 'Solicitud enviada correctamente. Un administrador revisará tu solicitud.');
    }

    /**
     * Ver archivos compartidos conmigo
     */
    public function compartidosConmigo(Request $request)
    {
        $query = DescargaArchivo::with(['categoria', 'user', 'compartidoPor', 'roles'])
            ->activos()
            ->noExpirados()
            ->compartidos()
            ->whereHas('usuarios', function ($q) {
                $q->where('users.id', Auth::id());
            });

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre_original', 'like', "%{$busqueda}%")
                  ->orWhere('extension', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        $orden = $request->input('orden', 'recientes');
        switch ($orden) {
            case 'nombre':
                $query->orderBy('nombre_original');
                break;
            case 'descargas':
                $query->orderByDesc('descargas_count');
                break;
            case 'tamano':
                $query->orderByDesc('tamano_bytes');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $archivos = $query->paginate(20)->withQueryString();
        $categorias = DescargaCategoria::activas()->ordenadas()->get();

        return view('herramientas.descargas.compartidos', compact('archivos', 'categorias'));
    }

    /**
     * Marcar/desmarcar archivo como favorito
     */
    public function toggleFavorito(DescargaArchivo $archivo)
    {
        $this->autorizarArchivo($archivo);

        $favorito = DescargaFavorito::where('user_id', Auth::id())
            ->where('archivo_id', $archivo->id)
            ->first();

        if ($favorito) {
            $favorito->delete();
            return response()->json([
                'success' => true,
                'es_favorito' => false,
                'message' => 'Archivo removido de favoritos'
            ]);
        } else {
            DescargaFavorito::create([
                'user_id' => Auth::id(),
                'archivo_id' => $archivo->id,
            ]);
            return response()->json([
                'success' => true,
                'es_favorito' => true,
                'message' => 'Archivo agregado a favoritos'
            ]);
        }
    }

    /**
     * Ver mis archivos favoritos
     */
    public function misFavoritos(Request $request)
    {
        $query = DescargaArchivo::with(['categoria', 'user', 'roles'])
            ->activos()
            ->noExpirados()
            ->favoritosDe(Auth::id())
            ->accesiblesPor(Auth::user());

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre_original', 'like', "%{$busqueda}%")
                  ->orWhere('extension', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        $orden = $request->input('orden', 'recientes');
        switch ($orden) {
            case 'nombre':
                $query->orderBy('nombre_original');
                break;
            case 'descargas':
                $query->orderByDesc('descargas_count');
                break;
            case 'tamano':
                $query->orderByDesc('tamano_bytes');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $archivos = $query->paginate(20)->withQueryString();
        $categorias = DescargaCategoria::activas()->ordenadas()->get();

        return view('herramientas.descargas.favoritos', compact('archivos', 'categorias'));
    }

    /**
     * Ver mi historial de descargas
     */
    public function miHistorial(Request $request)
    {
        $query = DescargaLog::with(['archivo.categoria', 'archivo.user'])
            ->where('user_id', Auth::id())
            ->orderByDesc('downloaded_at');

        if ($request->filled('fecha_desde')) {
            $query->whereDate('downloaded_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('downloaded_at', '<=', $request->input('fecha_hasta'));
        }

        if ($request->filled('categoria_id')) {
            $query->whereHas('archivo', function ($q) use ($request) {
                $q->where('categoria_id', $request->input('categoria_id'));
            });
        }

        $logs = $query->paginate(30)->withQueryString();
        $categorias = DescargaCategoria::activas()->ordenadas()->get();

        // Estadísticas
        $totalDescargas = DescargaLog::where('user_id', Auth::id())->count();
        $descargasMes = DescargaLog::where('user_id', Auth::id())
            ->whereMonth('downloaded_at', now()->month)
            ->whereYear('downloaded_at', now()->year)
            ->count();
        $archivosUnicos = DescargaLog::where('user_id', Auth::id())
            ->distinct('archivo_id')
            ->count('archivo_id');

        return view('herramientas.descargas.historial', compact(
            'logs', 
            'categorias', 
            'totalDescargas', 
            'descargasMes', 
            'archivosUnicos'
        ));
    }

    /**
     * Solicitar descarga masiva en ZIP
     */
    public function solicitarZip(Request $request)
    {
        $request->validate([
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'exists:descarga_archivos,id',
        ]);

        $archivosIds = $request->input('archivos');
        $archivos = DescargaArchivo::whereIn('id', $archivosIds)
            ->activos()
            ->noExpirados()
            ->get();

        // Verificar que el usuario tiene acceso a todos los archivos
        foreach ($archivos as $archivo) {
            if (!$archivo->puedeDescargar(Auth::user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para descargar uno o más archivos seleccionados'
                ], 403);
            }
        }

        // Calcular tamaño total
        $tamanoTotal = $archivos->sum('tamano_bytes');
        $tamanoMaximoBytes = config('descargas.zip_tamano_maximo_gb', 10) * 1024 * 1024 * 1024;

        if ($tamanoTotal > $tamanoMaximoBytes) {
            return response()->json([
                'success' => false,
                'message' => 'El tamaño total de los archivos seleccionados supera el límite de ' . 
                             config('descargas.zip_tamano_maximo_gb', 10) . ' GB'
            ], 400);
        }

        // Crear el ZIP
        $zipNombre = 'descargas_' . Auth::id() . '_' . time() . '.zip';
        $zipPath = 'temp/' . $zipNombre;
        $zipFullPath = Storage::disk('descargas')->path($zipPath);

        // Asegurar que el directorio existe
        if (!Storage::disk('descargas')->exists('temp')) {
            Storage::disk('descargas')->makeDirectory('temp');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el archivo ZIP'
            ], 500);
        }

        $repositorio = app(DescargaRepositorio::class);
        $archivosAgregados = 0;

        foreach ($archivos as $archivo) {
            if ($repositorio->existeArchivo($archivo)) {
                $rutaAbsoluta = $repositorio->obtenerRutaAbsoluta($archivo);
                $zip->addFile($rutaAbsoluta, $archivo->nombre_original);
                $archivosAgregados++;
            }
        }

        $zip->close();

        if ($archivosAgregados === 0) {
            Storage::disk('descargas')->delete($zipPath);
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron agregar archivos al ZIP'
            ], 500);
        }

        // Registrar en la base de datos
        $zipTemporal = DescargaZipTemporal::create([
            'user_id' => Auth::id(),
            'token' => uniqid('zip_'),
            'ruta_zip' => $zipPath,
            'tamano_bytes' => filesize($zipFullPath),
            'expira_at' => now()->addHours(config('descargas.zip_temp_expiracion_horas', 24)),
            'descargado' => false,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ZIP creado exitosamente',
            'download_url' => route('descargas.descargar-zip', $zipTemporal->token),
            'tamano' => $zipTemporal->tamano_humano,
            'archivos' => $archivosAgregados
        ]);
    }

    /**
     * Descargar ZIP temporal
     */
    public function descargarZip($token)
    {
        $zipTemporal = DescargaZipTemporal::where('token', $token)
            ->where('user_id', Auth::id())
            ->first();

        if (!$zipTemporal) {
            abort(404, 'ZIP no encontrado');
        }

        if ($zipTemporal->expira_at->isPast()) {
            abort(410, 'El ZIP ha expirado');
        }

        if (!Storage::disk('descargas')->exists($zipTemporal->ruta_zip)) {
            abort(404, 'El archivo ZIP ya no está disponible');
        }

        // Marcar como descargado
        $zipTemporal->update(['descargado' => true]);

        // Registrar en logs
        DescargaLog::create([
            'archivo_id' => null,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'link_publico_id' => null,
            'downloaded_at' => now(),
        ]);

        return response()->download(
            Storage::disk('descargas')->path($zipTemporal->ruta_zip),
            'descargas_' . date('Y-m-d_H-i-s') . '.zip'
        );
    }

    /**
     * Descargar archivo usando código QR
     */
    public function descargarConQr($token, Request $request)
    {
        $qrCode = DescargaQrCode::where('token', $token)
            ->where('activo', true)
            ->first();

        if (!$qrCode) {
            abort(404, 'Código QR no encontrado o desactivado');
        }

        if ($qrCode->expira_at->isPast()) {
            abort(410, 'El código QR ha expirado');
        }

        if ($qrCode->usos_count >= $qrCode->max_usos) {
            abort(410, 'El código QR ya fue utilizado');
        }

        // Verificar password si es necesario
        if ($qrCode->password) {
            if (!$request->has('password')) {
                return view('herramientas.descargas.qr_password', compact('qrCode'));
            }

            if (!Hash::check($request->input('password'), $qrCode->password)) {
                return view('herramientas.descargas.qr_password', [
                    'qrCode' => $qrCode,
                    'error' => 'Contraseña incorrecta'
                ]);
            }
        }

        $archivo = $qrCode->archivo;

        if (!$archivo || !$archivo->activo || $archivo->esta_expirado) {
            abort(404, 'El archivo no está disponible');
        }

        $repositorio = app(DescargaRepositorio::class);

        if (!$repositorio->existeArchivo($archivo)) {
            abort(404, 'El archivo no se encuentra disponible');
        }

        // Registrar uso del QR
        $qrCode->increment('usos_count');

        // Registrar descarga en logs
        DescargaLog::create([
            'archivo_id' => $archivo->id,
            'user_id' => null, // Descarga anónima vía QR
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'link_publico_id' => null,
            'downloaded_at' => now(),
        ]);

        // Incrementar contador de descargas
        $archivo->increment('descargas_count');

        return response()->download(
            $repositorio->obtenerRutaAbsoluta($archivo),
            $archivo->nombre_original
        );
    }
}
