<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Recurso;
use App\Models\VehiculoPrestamo;
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

        // Vehículos agrupados por sección
        $secciones = Destino::whereIn('id', $todosLosDestinoIds)
            ->with([
                'recursos' => function ($q) {
                    $q->whereNotNull('vehiculo_id')
                      ->with([
                          'vehiculo',
                          'vehiculo.estadoSeccion',
                          'vehiculo.novedadesPendientes',
                          'vehiculo.prestamoActivo.destinoDestino',
                          'vehiculo.estadoDiarioHoy',
                      ]);
                },
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());

        $prestamosActivos = VehiculoPrestamo::activos()
            ->whereIn('destino_origen_id', $todosLosDestinoIds)
            ->with(['vehiculo', 'destinoDestino'])
            ->orderByDesc('fecha_salida')
            ->get();

        // Contadores globales
        $totalVehiculos = $secciones->sum(fn($s) => $s->recursos->count());
        $totalNovedadesPendientes = $secciones->sum(
            fn($s) => $s->recursos->sum(fn($r) => $r->vehiculo?->novedadesPendientes->count() ?? 0)
        );
        $totalPrestados = $prestamosActivos->count();

        return view('flota-911.dashboard', compact(
            'division',
            'secciones',
            'prestamosActivos',
            'totalVehiculos',
            'totalNovedadesPendientes',
            'totalPrestados',
        ));
    }
}
