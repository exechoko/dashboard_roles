<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\FlotaGeneral;
use App\Models\PatrimonioCargo;
use Illuminate\Http\Request;

class PatrimonioCargoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-patrimonio-cargos')->only(['index', 'show']);
        $this->middleware('permission:firmar-patrimonio-cargos')->only(['firmar', 'rechazar']);
        $this->middleware('permission:gestionar-patrimonio')->only(['dashboard']);
    }

    /**
     * Listado de cargos patrimoniales con filtros
     */
    public function index(Request $request)
    {
        $query = PatrimonioCargo::with([
            'equipo:id,tei,issi,tipo_terminal_id',
            'equipo.tipo_terminal:id,marca,modelo',
            'destino:id,nombre,parent_id',
            'destino.padre:id,nombre',
        ]);

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por dependencia (incluye hijos recursivos)
        if ($request->filled('destino_id')) {
            $destinoIds = Destino::obtenerTodosLosHijos($request->destino_id);
            $query->whereIn('destino_id', $destinoIds);
        }

        // Filtro por texto (TEI, ISSI, firmante)
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('firmante_nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('firmante_legajo', 'like', '%' . $busqueda . '%')
                    ->orWhereHas('equipo', function ($sub) use ($busqueda) {
                        $sub->where('tei', 'like', '%' . $busqueda . '%')
                            ->orWhere('issi', 'like', '%' . $busqueda . '%');
                    });
            });
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->where('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', $request->fecha_hasta . ' 23:59:59');
        }

        $cargos = $query->orderBy('created_at', 'desc')->paginate(20);
        $destinos = Destino::orderBy('nombre')->get();

        // Contadores por estado
        $contadores = [
            'total'      => PatrimonioCargo::count(),
            'pendientes' => PatrimonioCargo::where('estado', 'pendiente')->count(),
            'firmados'   => PatrimonioCargo::where('estado', 'firmado')->count(),
            'rechazados' => PatrimonioCargo::where('estado', 'rechazado')->count(),
        ];

        return view('patrimonio.cargos.index', compact('cargos', 'destinos', 'contadores'));
    }

    /**
     * Detalle de un cargo patrimonial
     */
    public function show($id)
    {
        $cargo = PatrimonioCargo::with([
            'equipo',
            'equipo.tipo_terminal',
            'equipo.estado',
            'destino',
            'destino.padre',
            'firmanteDestino',
            'historico',
            'historico.tipoMovimiento',
        ])->findOrFail($id);

        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.cargos.show', compact('cargo', 'destinos'));
    }

    /**
     * Firmar un cargo patrimonial
     */
    public function firmar(Request $request, $id)
    {
        $request->validate([
            'firmante_nombre'     => 'required|string|max:150',
            'firmante_cargo'      => 'nullable|string|max:150',
            'firmante_legajo'     => 'nullable|string|max:50',
            'firmante_destino_id' => 'required|exists:destino,id',
            'observaciones'       => 'nullable|string',
        ]);

        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->estaPendiente()) {
            return back()->with('error', 'Este cargo ya fue procesado');
        }

        $cargo->firmar(
            $request->firmante_nombre,
            $request->firmante_cargo,
            $request->firmante_legajo,
            $request->observaciones,
            $request->firmante_destino_id
        );

        return redirect()->route('patrimonio.cargos.show', $cargo->id)
            ->with('success', 'Cargo patrimonial firmado exitosamente');
    }

    /**
     * Rechazar un cargo patrimonial
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'observaciones' => 'required|string',
        ]);

        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->estaPendiente()) {
            return back()->with('error', 'Este cargo ya fue procesado');
        }

        $cargo->rechazar($request->observaciones);

        // Al rechazar, limpiar patrimonio del equipo en flota
        $flota = FlotaGeneral::where('equipo_id', $cargo->equipo_id)->first();
        if ($flota && $flota->cargo_id === $cargo->id) {
            $flota->despatrimoniar();
        }

        return redirect()->route('patrimonio.cargos.show', $cargo->id)
            ->with('success', 'Cargo patrimonial rechazado');
    }

    /**
     * Dashboard patrimonial por dependencia
     */
    public function dashboard(Request $request)
    {
        // Obtener departamentales como nivel principal
        $departamentales = Destino::where('tipo', 'departamental')
            ->with(['hijos' => function ($q) {
                $q->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get();

        // Calcular estadísticas por cada departamental (incluyendo hijos)
        foreach ($departamentales as $departamental) {
            $departamental->stats = $departamental->getEstadisticasPatrimoniales(true);

            // Stats de cada hijo directo
            foreach ($departamental->hijos as $hijo) {
                $hijo->stats = $hijo->getEstadisticasPatrimoniales(true);
            }
        }

        // Totales generales
        $totales = [
            'patrimoniados'    => FlotaGeneral::where('patrimoniado', true)->count(),
            'pendientes_firma' => FlotaGeneral::where('patrimoniado', true)
                ->whereHas('cargo', fn($q) => $q->where('estado', 'pendiente'))
                ->count(),
            'sin_patrimoniar'  => FlotaGeneral::where('patrimoniado', false)->count(),
            'total_flota'      => FlotaGeneral::count(),
        ];

        // También incluir direcciones y otras dependencias de primer nivel
        $direcciones = Destino::where('tipo', 'direccion')
            ->orderBy('nombre')
            ->get();

        foreach ($direcciones as $direccion) {
            $direccion->stats = $direccion->getEstadisticasPatrimoniales(true);
        }

        return view('patrimonio.dashboard', compact('departamentales', 'direcciones', 'totales'));
    }
}
