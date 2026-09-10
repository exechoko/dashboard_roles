<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\RecursoPrestamo;
use App\Models\RecursoTransferencia;
use Illuminate\Http\Request;

class FlotaDashboard911Controller extends Controller
{
    private const DIVISION_911_ID = 42;

    public function __construct()
    {
        $this->middleware('can:ver-flota-911');
    }

    public function index()
    {
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();

        $secciones = Destino::whereIn('id', $todosLosDestinoIds)
            ->with([
                'recursos' => function ($q) {
                    // Solo recursos con vehículo asignado (vehiculo_id directo).
                    // Cuando se implemente rotación vía recurso_vehiculo_asignaciones,
                    // cambiar a whereHas('asignacionActual').
                    $q->activos()->whereNotNull('vehiculo_id')->with([
                        'vehiculo',
                        'asignacionActual.vehiculo',
                        'estadoSeccion',
                        'bitacoraAbiertas',
                        'prestamoActivo.destinoDestino',
                        'estadoDiarioHoy',
                        'transferenciaPendiente',
                    ]);
                },
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());

        $prestamosActivos = RecursoPrestamo::activos()
            ->whereIn('destino_origen_id', $todosLosDestinoIds)
            ->with(['recurso', 'vehiculoSnapshot', 'destinoDestino'])
            ->orderByDesc('fecha_salida')
            ->get();

        $totalRecursos = $secciones->sum(fn($s) => $s->recursos->count());
        $totalEnTaller = $secciones->sum(
            fn($s) => $s->recursos->filter(fn($r) => $r->bitacoraAbiertas->isNotEmpty())->count()
        );
        $totalPrestados = $prestamosActivos->count();

        $transferenciasPendientes = auth()->user()->can('confirmar-transferencia-recurso')
            ? RecursoTransferencia::pendientes()
                ->whereHas('recurso', fn($q) => $q->whereIn('destino_id', $todosLosDestinoIds))
                ->count()
            : 0;

        $destinos = Destino::opcionesConJerarquia();

        return view('flota-911.dashboard', compact(
            'division',
            'secciones',
            'prestamosActivos',
            'totalRecursos',
            'totalEnTaller',
            'totalPrestados',
            'transferenciasPendientes',
            'destinos',
        ));
    }
}
