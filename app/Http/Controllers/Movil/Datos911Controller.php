<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\Antena;
use App\Models\Camara;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\EventoCecoco;
use App\Models\LlamadaCentralTelefonica;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\Sitio;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Datos911Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-datos-911');
    }

    public function index()
    {
        return view('movil.datos911.index');
    }

    public function datosJson(): JsonResponse
    {
        $usuario = auth()->user();

        $data = [];

        if ($usuario->can('ver-camara')) {
            $data['camaras'] = $this->datosCamaras();
        }

        if ($usuario->can('ver-personal')) {
            $data['personal'] = $this->datosPersonal();
        }

        if ($usuario->canAny(['ver-equipo', 'ver-antena'])) {
            $data['tetra'] = $this->datosTetra($usuario);
        }

        if ($usuario->can('ver-vehiculo')) {
            $data['patrullaje'] = $this->datosPatrullaje();
        }

        if ($usuario->canAny(['ver-reporte-llamadas-central-telefonica', 'ver-analitica-eventos-cecoco'])) {
            $data['cecoco'] = $this->datosCecoco($usuario, Carbon::today());
        }

        return response()->json($data);
    }

    /**
     * Recalcula solo el bloque de CeCoCo (llamadas/detenciones) para una
     * fecha puntual, sin volver a consultar el resto de la pantalla.
     */
    public function cecocoJson(Request $request): JsonResponse
    {
        $usuario = auth()->user();

        abort_unless(
            $usuario->canAny(['ver-reporte-llamadas-central-telefonica', 'ver-analitica-eventos-cecoco']),
            403
        );

        $validated = $request->validate([
            'fecha' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $fecha = isset($validated['fecha']) ? Carbon::parse($validated['fecha']) : Carbon::today();

        return response()->json($this->datosCecoco($usuario, $fecha));
    }

    /**
     * @return array<string, mixed>
     */
    private function datosCamaras(): array
    {
        $porTipo = Camara::select('tipo_camara.tipo', DB::raw('COUNT(*) as total'))
            ->join('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->groupBy('tipo_camara.tipo')
            ->orderBy('tipo_camara.tipo')
            ->get()
            ->map(fn ($fila) => ['tipo' => $fila->tipo, 'total' => (int) $fila->total]);

        return [
            'total' => Camara::count(),
            'por_tipo' => $porTipo,
            'sitios_activos' => Sitio::where('activo', 1)->count(),
            'sitios_inactivos' => Sitio::where('activo', 0)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function datosPersonal(): array
    {
        return [
            'activos' => Personal::count(),
            'de_licencia' => Personal::whereHas('licencias', fn ($q) => $q->vigentes())->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datosTetra(Authenticatable $usuario): array
    {
        $tetra = [];

        if ($usuario->can('ver-equipo')) {
            $stock911 = Recurso::where('nombre', 'Stock 911')->first();

            $funcionalesPorTipo = Equipo::select('tipo_terminales.marca', 'tipo_terminales.modelo', DB::raw('COUNT(*) as total'))
                ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
                ->operativo()
                ->conAccesoriosCompletos()
                ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo')
                ->get()
                ->map(fn ($fila) => ['tipo' => trim($fila->marca . ' ' . $fila->modelo), 'total' => (int) $fila->total]);

            $enStockPorTipo = collect();
            if ($stock911) {
                $enStockPorTipo = Equipo::select('tipo_terminales.marca', 'tipo_terminales.modelo', DB::raw('COUNT(DISTINCT equipos.id) as total'))
                    ->join('flota_general', 'equipos.id', '=', 'flota_general.equipo_id')
                    ->join('tipo_terminales', 'equipos.tipo_terminal_id', '=', 'tipo_terminales.id')
                    ->where('flota_general.recurso_id', $stock911->id)
                    ->whereNull('flota_general.fecha_desasignacion')
                    ->groupBy('tipo_terminales.id', 'tipo_terminales.marca', 'tipo_terminales.modelo')
                    ->get()
                    ->map(fn ($fila) => ['tipo' => trim($fila->marca . ' ' . $fila->modelo), 'total' => (int) $fila->total]);
            }

            $tetra['equipos'] = [
                'funcionales_total' => (int) $funcionalesPorTipo->sum('total'),
                'funcionales_por_tipo' => $funcionalesPorTipo->values(),
                'en_stock_total' => (int) $enStockPorTipo->sum('total'),
                'en_stock_por_tipo' => $enStockPorTipo->values(),
            ];
        }

        if ($usuario->can('ver-antena')) {
            $tetra['sbs'] = [
                'total' => Antena::count(),
                'activas' => Antena::where('activa', true)->count(),
                'inactivas' => Antena::where('activa', false)->count(),
            ];
        }

        return $tetra;
    }

    /**
     * Móviles del 911 (Sección Patrulla + Sección Patrulla Motorizada, bajo
     * la División 911 y Videovigilancia), discriminados por tipo de vehículo.
     *
     * @return array<string, int>
     */
    private function datosPatrullaje(): array
    {
        $destinoIds = Destino::whereIn('nombre', ['Sección Patrulla', 'Sección Patrulla Motorizada'])->pluck('id');

        $porTipo = Recurso::whereIn('destino_id', $destinoIds)
            ->whereNotNull('vehiculo_id')
            ->join('vehiculos', 'vehiculos.id', '=', 'recursos.vehiculo_id')
            ->selectRaw('vehiculos.tipo_vehiculo, COUNT(*) as total')
            ->groupBy('vehiculos.tipo_vehiculo')
            ->pluck('total', 'vehiculos.tipo_vehiculo');

        return [
            'camionetas' => (int) ($porTipo['Camioneta'] ?? 0),
            'autos' => (int) ($porTipo['Auto'] ?? 0),
            'motos' => (int) ($porTipo['Moto'] ?? 0),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function datosCecoco(Authenticatable $usuario, Carbon $fecha): array
    {
        $inicio = $fecha->copy()->startOfDay();
        $fin = $fecha->isToday() ? Carbon::now() : $fecha->copy()->endOfDay();

        $cecoco = [
            'fecha' => $fecha->format('Y-m-d'),
            'fecha_legible' => $fecha->format('d/m/Y'),
        ];

        if ($usuario->can('ver-reporte-llamadas-central-telefonica')) {
            $cecoco['llamadas_ultimo_dia'] = LlamadaCentralTelefonica::recibidas()
                ->whereBetween('calldate', [$inicio, $fin])
                ->count();
        }

        if ($usuario->can('ver-analitica-eventos-cecoco')) {
            $cecoco['detenciones_ultimo_dia'] = EventoCecoco::whereBetween('fecha_hora', [$inicio, $fin])
                ->where(function ($query) {
                    $query->where(function ($sub) {
                        $sub->whereNotNull('descripcion')
                            ->whereRaw('descripcion REGEXP ?', ['detenid[oa]|demorad[oa]|aprehend|aprehensi[oó]n']);
                    })->orWhere(DB::raw('LOWER(tipo_servicio)'), 'like', '%con detenidos%');
                })
                ->count();
        }

        return $cecoco;
    }
}
