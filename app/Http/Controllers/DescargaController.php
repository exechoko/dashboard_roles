<?php

namespace App\Http\Controllers;

use App\Models\DescargaArchivo;
use App\Models\DescargaCategoria;
use App\Models\DescargaComentario;
use App\Models\DescargaLinkPublico;
use App\Models\DescargaLog;
use App\Services\Descargas\DescargaRepositorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DescargaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-plataforma-descargas');
    }

    public function index(Request $request)
    {
        $query = DescargaArchivo::with(['categoria', 'user', 'roles'])
            ->activos()
            ->noExpirados()
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

        if ($request->filled('extension')) {
            $query->where('extension', $request->input('extension'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
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

        return view('herramientas.descargas.index', compact('archivos', 'categorias', 'extensiones'));
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
}
