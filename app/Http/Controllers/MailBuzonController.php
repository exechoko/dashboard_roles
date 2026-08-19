<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMailBuzonRequest;
use App\Http\Requests\UpdateMailBuzonRequest;
use App\Jobs\IndexarArchivoMbox;
use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Services\Mbox\MboxRepositorioArchivos;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MailBuzonController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:administrar-visor-mails');
    }

    public function index(): View
    {
        // No se limita el eager load de archivos a 1 por buzón: con una constraint
        // "limit()" en with(), Eloquent aplica el límite a la consulta combinada de
        // todos los buzones, no por buzón (el fix real es latestOfMany(), de más
        // para una relación que en la práctica tiene pocos archivos por oficina).
        $buzones = MailBuzon::withCount('mensajes')
            ->with(['role', 'archivos' => fn ($q) => $q->latest('id')])
            ->orderBy('nombre')
            ->get();

        return view('herramientas.mails.buzones.index', compact('buzones'));
    }

    public function detectarOficinas(MboxRepositorioArchivos $repositorio): View
    {
        $raiz = $repositorio->raiz();
        $oficinas = $repositorio->oficinas();

        return view('herramientas.mails.buzones.detectar', compact('raiz', 'oficinas'));
    }

    public function registrarOficinas(Request $request, MboxRepositorioArchivos $repositorio): RedirectResponse
    {
        $carpetas = (array) $request->input('carpetas', []);
        $existentes = MailBuzon::pluck('carpeta')->all();
        $disponibles = collect($repositorio->oficinas())->keyBy('carpeta');

        $creados = 0;
        foreach ($carpetas as $carpeta) {
            if (in_array($carpeta, $existentes, true) || !$disponibles->has($carpeta)) {
                continue;
            }

            MailBuzon::create([
                'nombre' => $disponibles[$carpeta]['nombre_sugerido'],
                'carpeta' => $carpeta,
                'activo' => true,
            ]);
            $creados++;
        }

        return redirect()->route('herramientas.mails.buzones.index')
            ->with('success', "Se dieron de alta {$creados} buzones. Ahora asignales un rol a cada uno.");
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('herramientas.mails.buzones.form', ['buzon' => new MailBuzon(), 'roles' => $roles]);
    }

    public function store(StoreMailBuzonRequest $request): RedirectResponse
    {
        MailBuzon::create($request->validated());

        return redirect()->route('herramientas.mails.buzones.index')->with('success', 'Buzón creado.');
    }

    public function edit(MailBuzon $buzon): View
    {
        $roles = Role::orderBy('name')->get();

        return view('herramientas.mails.buzones.form', compact('buzon', 'roles'));
    }

    public function update(UpdateMailBuzonRequest $request, MailBuzon $buzon): RedirectResponse
    {
        $buzon->update($request->validated());

        return redirect()->route('herramientas.mails.buzones.index')->with('success', 'Buzón actualizado.');
    }

    public function destroy(MailBuzon $buzon): RedirectResponse
    {
        $buzon->delete();

        return redirect()->route('herramientas.mails.buzones.index')->with('success', 'Buzón eliminado (los .mbox del disco no se tocan).');
    }

    public function archivos(MailBuzon $buzon, MboxRepositorioArchivos $repositorio): View
    {
        $hallados = $repositorio->archivosDe($buzon);

        return view('herramientas.mails.buzones.archivos', compact('buzon', 'hallados'));
    }

    public function registrarArchivo(Request $request, MailBuzon $buzon, MboxRepositorioArchivos $repositorio): RedirectResponse
    {
        $request->validate(['ruta_absoluta' => 'required|string']);

        $ruta = $repositorio->validarRutaMbox($request->input('ruta_absoluta'));

        $archivo = MailArchivo::firstOrNew(['buzon_id' => $buzon->id, 'ruta_absoluta' => $ruta]);
        $archivo->fill([
            'nombre_archivo' => basename($ruta),
            'tamano_bytes' => filesize($ruta),
            'mtime_archivo' => (new \DateTimeImmutable())->setTimestamp(filemtime($ruta)),
            'estado' => 'pendiente',
            'error_message' => null,
        ])->save();

        IndexarArchivoMbox::dispatch($archivo->id);

        return redirect()->route('herramientas.mails.buzones.archivos', $buzon)
            ->with('success', "Se encoló la indexación de {$archivo->nombre_archivo}.");
    }

    public function indexar(MailArchivo $archivo, Request $request): RedirectResponse
    {
        $archivo->update(['estado' => 'pendiente', 'error_message' => null]);

        IndexarArchivoMbox::dispatch($archivo->id, (bool) $request->boolean('reiniciar'));

        return redirect()->route('herramientas.mails.buzones.archivos', $archivo->buzon_id)
            ->with('success', "Se encoló la indexación de {$archivo->nombre_archivo}.");
    }

    public function estado(MailArchivo $archivo): JsonResponse
    {
        return response()->json([
            'estado' => $archivo->estado,
            'porcentaje' => $archivo->porcentaje_avance,
            'mensajes_total' => $archivo->mensajes_total,
            'mensajes_nuevos' => $archivo->mensajes_nuevos,
            'error_message' => $archivo->error_message,
        ]);
    }

    public function destroyArchivo(MailArchivo $archivo): RedirectResponse
    {
        $buzonId = $archivo->buzon_id;
        $archivo->delete();

        return redirect()->route('herramientas.mails.buzones.archivos', $buzonId)
            ->with('success', 'Se borró el índice de ese archivo (el .mbox del disco no se toca).');
    }
}
