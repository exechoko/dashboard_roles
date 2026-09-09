<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Recurso;
use App\Models\RecursoTransferencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecursoTransferenciaController extends Controller
{
    private const DIVISION_911_ID = 42;

    public function __construct()
    {
        $this->middleware('can:ver-flota-911')->only('index');
        $this->middleware('can:gestionar-flota-911')->only('store');
        $this->middleware('can:confirmar-transferencia-recurso')->only(['confirmar', 'rechazar', 'reactivar']);
    }

    public function index(): View
    {
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $destinoIds = $division->getDestinosHijosRecursivo();

        $relaciones = [
            'recurso.destino',
            'vehiculo',
            'destinoTransferencia.padre.padre.padre.padre',
            'usuarioReporte',
            'usuarioResolucion',
        ];

        $pendientes = RecursoTransferencia::pendientes()
            ->whereHas('recurso', fn ($q) => $q->whereIn('destino_id', $destinoIds))
            ->with($relaciones)
            ->orderBy('fecha_transferencia')
            ->get();

        $historial = RecursoTransferencia::resueltas()
            ->whereHas('recurso', fn ($q) => $q->whereIn('destino_id', $destinoIds))
            ->with($relaciones)
            ->orderByDesc('fecha_resolucion')
            ->paginate(20);

        $destinos = Destino::opcionesConJerarquia();

        return view('flota-911.transferencias.index', compact('pendientes', 'historial', 'destinos'));
    }

    public function store(Request $request, Recurso $recurso): RedirectResponse
    {
        if ($recurso->estaTransferido()) {
            return back()->with('error', 'El recurso ya figura como transferido.');
        }

        if ($recurso->transferenciaPendiente()->exists()) {
            return back()->with('error', 'El recurso ya tiene una transferencia pendiente de confirmar.');
        }

        $datos = $request->validate([
            'fecha_transferencia'      => 'required|date',
            'destino_transferencia_id' => 'nullable|exists:destino,id',
            'reparticion_texto'        => 'nullable|string|max:255',
            'observaciones'            => 'nullable|string|max:2000',
        ], [
            'fecha_transferencia.required' => 'La fecha de la transferencia es obligatoria.',
        ]);

        RecursoTransferencia::create([
            'recurso_id'               => $recurso->id,
            'vehiculo_id'              => $recurso->vehiculoActual()?->id,
            'destino_transferencia_id' => $datos['destino_transferencia_id'] ?? null,
            'reparticion_texto'        => $datos['reparticion_texto'] ?? null,
            'fecha_transferencia'      => $datos['fecha_transferencia'],
            'observaciones'            => $datos['observaciones'] ?? null,
            'estado'                   => RecursoTransferencia::ESTADO_PENDIENTE,
            'user_id_reporte'          => auth()->id(),
        ]);

        return back()->with('success', 'Transferencia reportada. Un administrador debe confirmarla tras hacer los movimientos.');
    }

    public function confirmar(Request $request, RecursoTransferencia $transferencia): RedirectResponse
    {
        if (! $transferencia->estaPendiente()) {
            return back()->with('error', 'La transferencia ya fue resuelta.');
        }

        $datos = $request->validate([
            'fecha_transferencia'      => 'required|date',
            'destino_transferencia_id' => 'nullable|exists:destino,id',
            'reparticion_texto'        => 'nullable|string|max:255',
            'observaciones'            => 'nullable|string|max:2000',
        ], [
            'fecha_transferencia.required' => 'La fecha de la transferencia es obligatoria.',
        ]);

        $transferencia->update([
            'destino_transferencia_id' => $datos['destino_transferencia_id'] ?? null,
            'reparticion_texto'        => $datos['reparticion_texto'] ?? null,
            'fecha_transferencia'      => $datos['fecha_transferencia'],
            'observaciones'            => $datos['observaciones'] ?? null,
            'estado'                   => RecursoTransferencia::ESTADO_CONFIRMADA,
            'user_id_resolucion'       => auth()->id(),
            'fecha_resolucion'         => now(),
        ]);

        $recurso = $transferencia->recurso;
        $recurso->fecha_transferencia = $datos['fecha_transferencia'];
        $recurso->destino_transferencia_id = $datos['destino_transferencia_id'] ?? null;
        $recurso->reparticion_transferencia = $datos['reparticion_texto'] ?? null;
        $recurso->observaciones_transferencia = $datos['observaciones'] ?? null;
        $recurso->save();

        return back()->with('success', 'Transferencia confirmada. El recurso pasó al listado de transferidos.');
    }

    public function rechazar(Request $request, RecursoTransferencia $transferencia): RedirectResponse
    {
        if (! $transferencia->estaPendiente()) {
            return back()->with('error', 'La transferencia ya fue resuelta.');
        }

        $datos = $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ], [
            'motivo_rechazo.required' => 'Indique el motivo del rechazo.',
        ]);

        $transferencia->update([
            'estado'             => RecursoTransferencia::ESTADO_RECHAZADA,
            'user_id_resolucion' => auth()->id(),
            'fecha_resolucion'   => now(),
            'motivo_rechazo'     => $datos['motivo_rechazo'],
        ]);

        return back()->with('success', 'Transferencia rechazada. El recurso sigue activo.');
    }

    public function reactivar(Recurso $recurso): RedirectResponse
    {
        if (! $recurso->estaTransferido()) {
            return back()->with('error', 'El recurso no figura como transferido.');
        }

        $confirmada = $recurso->transferencias()
            ->where('estado', RecursoTransferencia::ESTADO_CONFIRMADA)
            ->latest('id')
            ->first();

        RecursoTransferencia::create([
            'recurso_id'               => $recurso->id,
            'vehiculo_id'              => $recurso->vehiculoActual()?->id,
            'destino_transferencia_id' => $recurso->destino_transferencia_id,
            'reparticion_texto'        => $recurso->reparticion_transferencia,
            'fecha_transferencia'      => $recurso->fecha_transferencia?->toDateString() ?? now()->toDateString(),
            'observaciones'            => 'Reactivación del recurso en la flota.'
                . ($confirmada ? " Revierte la transferencia #{$confirmada->id}." : ''),
            'estado'                   => RecursoTransferencia::ESTADO_REACTIVADA,
            'user_id_reporte'          => auth()->id(),
            'user_id_resolucion'       => auth()->id(),
            'fecha_resolucion'         => now(),
        ]);

        $recurso->fecha_transferencia = null;
        $recurso->destino_transferencia_id = null;
        $recurso->reparticion_transferencia = null;
        $recurso->observaciones_transferencia = null;
        $recurso->save();

        return back()->with('success', 'Recurso reactivado en la flota. Queda registrado en el historial.');
    }
}
