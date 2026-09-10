<?php

namespace App\Http\Controllers;

use App\Models\RecursoBitacoraSolicitud;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BitacoraSolicitudController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:moderar-bitacora-flota-911');
    }

    public function index(): View
    {
        $relaciones = ['bitacora.recurso', 'usuario', 'resueltaPor'];

        $pendientes = RecursoBitacoraSolicitud::pendientes()
            ->with($relaciones)
            ->orderBy('created_at')
            ->get();

        $historial = RecursoBitacoraSolicitud::resueltas()
            ->with($relaciones)
            ->orderByDesc('resuelta_en')
            ->paginate(20);

        return view('flota-911.bitacora.solicitudes', compact('pendientes', 'historial'));
    }

    public function aprobar(RecursoBitacoraSolicitud $solicitud): RedirectResponse
    {
        if (! $solicitud->estaPendiente()) {
            return back()->with('error', 'La solicitud ya fue resuelta.');
        }

        DB::transaction(function () use ($solicitud): void {
            if ($solicitud->tipo === RecursoBitacoraSolicitud::TIPO_ELIMINACION) {
                $solicitud->bitacora?->delete();
            } elseif (! empty($solicitud->cambios)) {
                $solicitud->bitacora?->update($solicitud->cambios);
            }

            $solicitud->update([
                'estado'       => RecursoBitacoraSolicitud::ESTADO_APROBADA,
                'resuelta_por' => auth()->id(),
                'resuelta_en'  => now(),
            ]);
        });

        return back()->with('success', 'Solicitud aprobada.');
    }

    public function rechazar(Request $request, RecursoBitacoraSolicitud $solicitud): RedirectResponse
    {
        if (! $solicitud->estaPendiente()) {
            return back()->with('error', 'La solicitud ya fue resuelta.');
        }

        $datos = $request->validate([
            'motivo_resolucion' => ['required', 'string', 'max:500'],
        ], [
            'motivo_resolucion.required' => 'Indicá por qué se rechaza.',
        ]);

        $solicitud->update([
            'estado'            => RecursoBitacoraSolicitud::ESTADO_RECHAZADA,
            'motivo_resolucion' => $datos['motivo_resolucion'],
            'resuelta_por'      => auth()->id(),
            'resuelta_en'       => now(),
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }
}
