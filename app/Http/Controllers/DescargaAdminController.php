<?php

namespace App\Http\Controllers;

use App\Models\DescargaArchivo;
use App\Models\DescargaCategoria;
use App\Models\DescargaLinkPublico;
use App\Models\DescargaLog;
use App\Services\Descargas\DescargaNotificador;
use App\Services\Descargas\DescargaRepositorio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'archivos_config.*.destacado' => 'boolean',
            'archivos_config.*.expira_dias' => 'nullable|integer|min:1',
        ]);

        $archivosConfig = $request->input('archivos_config', []);
        $configs = [];
        
        foreach ($archivosConfig as $index => $config) {
            $expiraAt = !empty($config['expira_dias'])
                ? now()->addDays($config['expira_dias'])
                : null;
                
            $configs[$index] = [
                'categoria_id' => $config['categoria_id'],
                'descripcion' => $config['descripcion'] ?? null,
                'roles' => $config['roles'],
                'destacado' => !empty($config['destacado']),
                'expira_at' => $expiraAt,
                'user_id' => Auth::id(),
            ];
        }

        $archivosCreados = [];
        $conflictos = [];
        $tempDir = storage_path('app/temp_descargas');
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        foreach ($request->file('archivos') as $index => $archivo) {
            $conflicto = $this->repositorio->verificarConflicto($archivo->getClientOriginalName());

            if ($conflicto) {
                $size = $archivo->getSize();
                $mimeType = $archivo->getMimeType();
                $originalName = $archivo->getClientOriginalName();
                
                $tempFile = $tempDir . '/' . uniqid() . '_' . $originalName;
                $archivo->move($tempDir, basename($tempFile));
                
                $conflictos[] = [
                    'temp_path' => $tempFile,
                    'original_name' => $originalName,
                    'size' => $size,
                    'mime_type' => $mimeType,
                    'conflicto_id' => $conflicto->id,
                    'conflicto_nombre' => $conflicto->nombre_original,
                    'config' => $configs[$index] ?? null,
                ];
                continue;
            }

            $archivosCreados[] = $this->repositorio->subirArchivo($archivo, $configs[$index]);
        }

        if (!empty($conflictos)) {
            session()->flash('conflictos', $conflictos);
            return redirect()->route('descargas.admin.resolver_conflictos');
        }

        $notificador = app(DescargaNotificador::class);
        foreach ($archivosCreados as $archivo) {
            $notificador->notificarNuevoArchivo($archivo);
        }

        return redirect()->route('descargas.admin.archivos')
            ->with('success', count($archivosCreados) . ' archivo(s) cargado(s) correctamente.');
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
}
